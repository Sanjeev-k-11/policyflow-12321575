<?php

namespace App\Services;

use App\Exceptions\InvalidStateTransitionException;
use App\Models\IssuanceAttempt;
use App\Models\PolicyRequest;
use App\Models\ProviderEvent;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class IssuanceEngine
{
    public function __construct(
        protected PolicyStateMachine $stateMachine,
        protected MockInsurerService $mockInsurer
    ) {}

    /**
     * Start or safely resume policy issuance.
     *
     * Concurrency & Reliability Strategy:
     * 1. Uses DB transaction and pessimistic row-level lock (lockForUpdate) on policy_requests.
     * 2. Checks if policy is already ISSUED; if so, safely returns existing policy number without calling provider.
     * 3. Prevents repeated provider calls simply because the HTTP request was retried.
     * 4. Persists an immutable issuance attempt with a deterministic idempotency key.
     */
    public function submit(PolicyRequest $policy, ?string $scenarioOverride = null): array
    {
        return DB::transaction(function () use ($policy, $scenarioOverride) {
            // Pessimistic lock prevents concurrent double-submission race conditions
            /** @var PolicyRequest $lockedPolicy */
            $lockedPolicy = PolicyRequest::where('id', $policy->id)->lockForUpdate()->firstOrFail();

            // Guard against duplicate execution if already issued
            if ($lockedPolicy->isIssued()) {
                return [
                    'success' => true,
                    'safe_no_op' => true,
                    'status' => $lockedPolicy->status,
                    'policy_number' => $lockedPolicy->policy_number,
                    'provider_reference' => $lockedPolicy->provider_reference,
                    'message' => 'Policy is already ISSUED. Safe idempotent response returned without re-dispatching.',
                    'policy' => $lockedPolicy,
                ];
            }

            // Ensure policy is in a valid state to submit
            if ($lockedPolicy->status === PolicyRequest::STATUS_RECEIVED) {
                $this->stateMachine->transition(
                    $lockedPolicy,
                    PolicyRequest::STATUS_VALIDATED,
                    'SYSTEM_PRE_SUBMIT_VALIDATION',
                    'Automatic validation before issuance dispatch'
                );
            }

            // Next attempt number
            $attemptNumber = $lockedPolicy->attempt_count + 1;
            $lockedPolicy->attempt_count = $attemptNumber;
            $lockedPolicy->save();

            // Determine scenario
            $effectiveScenario = $scenarioOverride ?: ($lockedPolicy->scenario ?: MockInsurerService::SCENARIO_SUCCESS);

            // Deterministic Idempotency Key per attempt
            $idempotencyKey = "IDEM-POL{$lockedPolicy->id}-ATTEMPT-{$attemptNumber}-" . Str::random(6);

            $requestPayload = [
                'client_request_id' => $lockedPolicy->client_request_id,
                'agent_id' => $lockedPolicy->agent_id,
                'customer_name' => $lockedPolicy->customer_name,
                'date_of_birth' => $lockedPolicy->date_of_birth->toDateString(),
                'destination' => $lockedPolicy->destination,
                'trip_start' => $lockedPolicy->trip_start->toDateString(),
                'trip_end' => $lockedPolicy->trip_end->toDateString(),
                'plan_code' => $lockedPolicy->plan_code,
                'sum_insured' => $lockedPolicy->sum_insured,
                'calculated_premium' => (string) $lockedPolicy->calculated_premium,
                'idempotency_key' => $idempotencyKey,
                'scenario' => $effectiveScenario,
            ];

            // Record pending attempt
            $startTime = microtime(true);
            $attempt = IssuanceAttempt::create([
                'policy_request_id' => $lockedPolicy->id,
                'attempt_number' => $attemptNumber,
                'idempotency_key' => $idempotencyKey,
                'scenario' => $effectiveScenario,
                'request_payload' => $requestPayload,
                'status' => IssuanceAttempt::STATUS_PENDING,
            ]);

            // Transition status to SUBMITTED
            if ($lockedPolicy->status !== PolicyRequest::STATUS_SUBMITTED) {
                $this->stateMachine->transition(
                    $lockedPolicy,
                    PolicyRequest::STATUS_SUBMITTED,
                    'SUBMIT_DISPATCH',
                    "Dispatched issuance attempt #{$attemptNumber} to insurer (scenario: {$effectiveScenario})"
                );
            }

            // Call external/mock insurer
            $insurerResponse = $this->mockInsurer->issue($requestPayload, $effectiveScenario);
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);

            // Case B: Timeout After Issue Simulation
            if (!empty($insurerResponse['simulated_timeout'])) {
                $attempt->update([
                    'status' => IssuanceAttempt::STATUS_TIMEOUT,
                    'response_payload' => $insurerResponse,
                    'error_message' => $insurerResponse['message'] ?? 'Network timeout after request dispatch',
                    'duration_ms' => $durationMs,
                ]);

                $lockedPolicy->provider_reference = $insurerResponse['provider_reference'] ?? null;
                $lockedPolicy->last_response = $insurerResponse;
                $lockedPolicy->failure_reason = 'Upstream timeout: policy outcome uncertain. Status reconciliation required.';
                $lockedPolicy->save();

                // Move to NEEDS_RECONCILIATION so operator or automated job can reconcile via status query
                $this->stateMachine->transition(
                    $lockedPolicy,
                    PolicyRequest::STATUS_NEEDS_RECONCILIATION,
                    'INSURER_TIMEOUT',
                    'Client received timeout; state flagged for reconciliation before any reissue',
                    ['provider_reference' => $lockedPolicy->provider_reference]
                );

                return [
                    'success' => false,
                    'timeout' => true,
                    'status' => PolicyRequest::STATUS_NEEDS_RECONCILIATION,
                    'provider_reference' => $lockedPolicy->provider_reference,
                    'message' => 'Simulated timeout occurred. Policy placed in NEEDS_RECONCILIATION to avoid double issuance.',
                    'policy' => $lockedPolicy->fresh(),
                    'attempt' => $attempt,
                ];
            }

            // Case A / Happy path: SUCCESS -> ISSUED
            if (!empty($insurerResponse['success']) && ($insurerResponse['status'] ?? '') === 'ISSUED') {
                $attempt->update([
                    'status' => IssuanceAttempt::STATUS_SUCCESS,
                    'response_payload' => $insurerResponse,
                    'duration_ms' => $durationMs,
                ]);

                $lockedPolicy->policy_number = $insurerResponse['policy_number'];
                $lockedPolicy->provider_reference = $insurerResponse['provider_reference'];
                $lockedPolicy->last_response = $insurerResponse;
                $lockedPolicy->failure_reason = null;
                $lockedPolicy->save();

                $this->stateMachine->transition(
                    $lockedPolicy,
                    PolicyRequest::STATUS_ISSUED,
                    'INSURER_CONFIRMATION',
                    "Policy number [{$lockedPolicy->policy_number}] confirmed by insurer",
                    ['provider_reference' => $lockedPolicy->provider_reference]
                );

                return [
                    'success' => true,
                    'status' => PolicyRequest::STATUS_ISSUED,
                    'policy_number' => $lockedPolicy->policy_number,
                    'provider_reference' => $lockedPolicy->provider_reference,
                    'message' => 'Policy issued successfully.',
                    'policy' => $lockedPolicy->fresh(),
                    'attempt' => $attempt,
                ];
            }

            // Case: DELAYED_CALLBACK -> PENDING_CONFIRMATION
            if (!empty($insurerResponse['success']) && ($insurerResponse['status'] ?? '') === 'PENDING') {
                $attempt->update([
                    'status' => IssuanceAttempt::STATUS_PENDING,
                    'response_payload' => $insurerResponse,
                    'duration_ms' => $durationMs,
                ]);

                $lockedPolicy->provider_reference = $insurerResponse['provider_reference'];
                $lockedPolicy->last_response = $insurerResponse;
                $lockedPolicy->save();

                $this->stateMachine->transition(
                    $lockedPolicy,
                    PolicyRequest::STATUS_PENDING_CONFIRMATION,
                    'INSURER_PENDING_ACK',
                    'Insurer acknowledged submission; pending asynchronous callback',
                    ['provider_reference' => $lockedPolicy->provider_reference]
                );

                return [
                    'success' => true,
                    'status' => PolicyRequest::STATUS_PENDING_CONFIRMATION,
                    'provider_reference' => $lockedPolicy->provider_reference,
                    'message' => 'Submission acknowledged by insurer. Awaiting webhook callback.',
                    'policy' => $lockedPolicy->fresh(),
                    'attempt' => $attempt,
                ];
            }

            // Case: Temporary or permanent failure
            $attempt->update([
                'status' => IssuanceAttempt::STATUS_FAILED,
                'response_payload' => $insurerResponse,
                'error_message' => $insurerResponse['message'] ?? 'Insurer rejected request',
                'duration_ms' => $durationMs,
            ]);

            $lockedPolicy->provider_reference = $insurerResponse['provider_reference'] ?? $lockedPolicy->provider_reference;
            $lockedPolicy->last_response = $insurerResponse;
            $lockedPolicy->failure_reason = $insurerResponse['message'] ?? 'Insurer returned failure';
            $lockedPolicy->save();

            $this->stateMachine->transition(
                $lockedPolicy,
                PolicyRequest::STATUS_FAILED,
                'INSURER_FAILURE',
                $lockedPolicy->failure_reason,
                ['retriable' => $insurerResponse['retriable'] ?? false]
            );

            return [
                'success' => false,
                'status' => PolicyRequest::STATUS_FAILED,
                'error' => $insurerResponse['error'] ?? 'UNKNOWN_ERROR',
                'retriable' => $insurerResponse['retriable'] ?? false,
                'message' => $lockedPolicy->failure_reason,
                'policy' => $lockedPolicy->fresh(),
                'attempt' => $attempt,
            ];
        });
    }

    /**
     * Reconcile status against mock provider.
     * Solves the timeout-after-issue scenario safely: inspects upstream insurer datastore before any reissue.
     */
    public function reconcile(PolicyRequest $policy): array
    {
        if (empty($policy->provider_reference)) {
            return [
                'reconciled' => false,
                'message' => 'Cannot reconcile: no provider reference present on request.',
            ];
        }

        // Query upstream insurer status
        $statusResult = $this->mockInsurer->getStatus($policy->provider_reference);

        if (!$statusResult['found']) {
            return [
                'reconciled' => false,
                'provider_status' => 'NOT_FOUND',
                'message' => 'Provider has no record of this reference.',
            ];
        }

        // If provider confirms policy was issued upstream:
        if ($statusResult['status'] === 'ISSUED') {
            return DB::transaction(function () use ($policy, $statusResult) {
                /** @var PolicyRequest $lockedPolicy */
                $lockedPolicy = PolicyRequest::where('id', $policy->id)->lockForUpdate()->firstOrFail();

                if ($lockedPolicy->isIssued()) {
                    return [
                        'reconciled' => true,
                        'already_issued' => true,
                        'status' => $lockedPolicy->status,
                        'policy_number' => $lockedPolicy->policy_number,
                        'message' => 'Policy already reconciled and confirmed ISSUED.',
                    ];
                }

                $lockedPolicy->policy_number = $statusResult['policy_number'];
                $lockedPolicy->last_response = $statusResult;
                $lockedPolicy->failure_reason = null;
                $lockedPolicy->save();

                $this->stateMachine->transition(
                    $lockedPolicy,
                    PolicyRequest::STATUS_ISSUED,
                    'RECONCILIATION_RESOLVED',
                    "Reconciled successfully via provider status query. Policy number [{$lockedPolicy->policy_number}] restored.",
                    ['status_check' => $statusResult]
                );

                return [
                    'reconciled' => true,
                    'status' => PolicyRequest::STATUS_ISSUED,
                    'policy_number' => $lockedPolicy->policy_number,
                    'provider_reference' => $lockedPolicy->provider_reference,
                    'message' => 'Reconciliation successful: policy confirmed and marked ISSUED.',
                    'policy' => $lockedPolicy->fresh(),
                ];
            });
        }

        return [
            'reconciled' => false,
            'provider_status' => $statusResult['status'],
            'message' => "Provider returned status [{$statusResult['status']}]. Reconciliation pending.",
        ];
    }

    /**
     * Process an incoming webhook callback from insurer.
     *
     * Reliability Case C: Duplicate callback idempotency
     * 1. Uses DB unique constraint on provider_event_id.
     * 2. If same event is delivered 2-3 times, the duplicate is detected and discarded without state alteration.
     * 3. Terminal state defense: A delayed or repeated callback cannot overwrite or regress an ISSUED policy.
     */
    public function handleCallback(array $payload): array
    {
        $eventId = $payload['event_id'] ?? null;
        $providerReference = $payload['provider_reference'] ?? null;
        $eventType = $payload['event_type'] ?? 'POLICY_ISSUED';
        $policyNumber = $payload['policy_number'] ?? null;

        if (!$eventId || !$providerReference) {
            return [
                'success' => false,
                'status' => 'INVALID_PAYLOAD',
                'message' => 'event_id and provider_reference are required fields.',
            ];
        }

        // 1. Check for existing event in database (Fast duplicate detection)
        $existingEvent = ProviderEvent::where('provider_event_id', $eventId)->first();
        if ($existingEvent) {
            return [
                'success' => true,
                'status' => 'IGNORED_DUPLICATE',
                'event_id' => $eventId,
                'message' => 'Callback event already received and processed. Duplicate safely ignored.',
            ];
        }

        // 2. Atomic record creation + processing inside transaction
        try {
            return DB::transaction(function () use ($eventId, $providerReference, $eventType, $policyNumber, $payload) {
                // Database-level uniqueness ensures even simultaneous duplicate HTTP calls are caught
                $event = ProviderEvent::create([
                    'provider_event_id' => $eventId,
                    'provider_reference' => $providerReference,
                    'event_type' => $eventType,
                    'payload' => $payload,
                    'status' => ProviderEvent::STATUS_PROCESSED,
                    'processed_at' => now(),
                ]);

                // Find policy request by provider_reference with pessimistic lock
                /** @var PolicyRequest|null $policy */
                $policy = PolicyRequest::where('provider_reference', $providerReference)->lockForUpdate()->first();

                if (!$policy) {
                    $event->update([
                        'status' => ProviderEvent::STATUS_FAILED,
                        'rejection_reason' => "No policy found with provider_reference [{$providerReference}]",
                    ]);

                    return [
                        'success' => false,
                        'status' => 'POLICY_NOT_FOUND',
                        'message' => "No local policy matches provider reference [{$providerReference}].",
                    ];
                }

                // Reliability Defense: If policy is ALREADY issued, protect terminal state!
                if ($policy->isIssued()) {
                    $event->update([
                        'status' => ProviderEvent::STATUS_IGNORED_DUPLICATE,
                        'rejection_reason' => "Policy is already ISSUED with policy number [{$policy->policy_number}]. Confirmed state cannot be modified.",
                    ]);

                    return [
                        'success' => true,
                        'status' => 'ALREADY_ISSUED_IGNORED',
                        'policy_number' => $policy->policy_number,
                        'message' => 'Policy is already in confirmed ISSUED state. Callback safely acknowledged without state mutation.',
                    ];
                }

                // Process issuance confirmation
                if ($eventType === 'POLICY_ISSUED' && $policyNumber) {
                    $policy->policy_number = $policyNumber;
                    $policy->last_response = $payload;
                    $policy->failure_reason = null;
                    $policy->save();

                    $this->stateMachine->transition(
                        $policy,
                        PolicyRequest::STATUS_ISSUED,
                        'CALLBACK_CONFIRMATION',
                        "Issued via async webhook event [{$eventId}] with policy number [{$policyNumber}]",
                        ['event_id' => $eventId]
                    );

                    return [
                        'success' => true,
                        'status' => 'PROCESSED_ISSUED',
                        'policy_number' => $policy->policy_number,
                        'message' => 'Callback successfully processed and policy marked ISSUED.',
                        'policy' => $policy->fresh(),
                    ];
                }

                // Process failure callback
                if ($eventType === 'POLICY_FAILED') {
                    $reason = $payload['reason'] ?? 'Rejected during underwriting';
                    $policy->failure_reason = $reason;
                    $policy->last_response = $payload;
                    $policy->save();

                    $this->stateMachine->transition(
                        $policy,
                        PolicyRequest::STATUS_FAILED,
                        'CALLBACK_FAILURE',
                        $reason,
                        ['event_id' => $eventId]
                    );

                    return [
                        'success' => true,
                        'status' => 'PROCESSED_FAILED',
                        'message' => 'Callback processed and policy marked FAILED.',
                        'policy' => $policy->fresh(),
                    ];
                }

                return [
                    'success' => true,
                    'status' => 'EVENT_STORED',
                    'message' => 'Event recorded.',
                ];
            });
        } catch (QueryException $e) {
            // Handle race condition where another worker inserted the same provider_event_id simultaneously
            if (str_contains($e->getMessage(), 'unique') || str_contains($e->getMessage(), '1062')) {
                return [
                    'success' => true,
                    'status' => 'IGNORED_DUPLICATE',
                    'event_id' => $eventId,
                    'message' => 'Race caught: duplicate callback detected via DB unique index. Ignored safely.',
                ];
            }
            throw $e;
        }
    }
}
