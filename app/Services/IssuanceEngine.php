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

    

    public function submit(PolicyRequest $policy, ?string $scenarioOverride = null): array
    {
        return DB::transaction(function () use ($policy, $scenarioOverride) {
            
            
            $lockedPolicy = PolicyRequest::where('id', $policy->id)->lockForUpdate()->firstOrFail();

            
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

            
            if ($lockedPolicy->status === PolicyRequest::STATUS_RECEIVED) {
                $this->stateMachine->transition(
                    $lockedPolicy,
                    PolicyRequest::STATUS_VALIDATED,
                    'SYSTEM_PRE_SUBMIT_VALIDATION',
                    'Automatic validation before issuance dispatch'
                );
            }

            
            $attemptNumber = $lockedPolicy->attempt_count + 1;
            $lockedPolicy->attempt_count = $attemptNumber;
            $lockedPolicy->save();

            
            $effectiveScenario = $scenarioOverride ?: ($lockedPolicy->scenario ?: MockInsurerService::SCENARIO_SUCCESS);

            
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

            
            $startTime = microtime(true);
            $attempt = IssuanceAttempt::create([
                'policy_request_id' => $lockedPolicy->id,
                'attempt_number' => $attemptNumber,
                'idempotency_key' => $idempotencyKey,
                'scenario' => $effectiveScenario,
                'request_payload' => $requestPayload,
                'status' => IssuanceAttempt::STATUS_PENDING,
            ]);

            
            if ($lockedPolicy->status !== PolicyRequest::STATUS_SUBMITTED) {
                $this->stateMachine->transition(
                    $lockedPolicy,
                    PolicyRequest::STATUS_SUBMITTED,
                    'SUBMIT_DISPATCH',
                    "Dispatched issuance attempt #{$attemptNumber} to insurer (scenario: {$effectiveScenario})"
                );
            }

            
            $insurerResponse = $this->mockInsurer->issue($requestPayload, $effectiveScenario);
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);

            
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

    

    public function reconcile(PolicyRequest $policy): array
    {
        $identifier = $policy->provider_reference ?: $policy->client_request_id;
        if (empty($identifier)) {
            return [
                'reconciled' => false,
                'message' => 'Cannot reconcile: no provider reference or client request ID present on request.',
            ];
        }

        
        $statusResult = $this->mockInsurer->getStatus($identifier);

        if (!$statusResult['found']) {
            return [
                'reconciled' => false,
                'provider_status' => 'NOT_FOUND',
                'message' => 'Provider has no record of this reference.',
            ];
        }

        
        if ($statusResult['status'] === 'ISSUED') {
            return DB::transaction(function () use ($policy, $statusResult) {
                
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

        
        $existingEvent = ProviderEvent::where('provider_event_id', $eventId)->first();
        if ($existingEvent) {
            return [
                'success' => true,
                'status' => 'IGNORED_DUPLICATE',
                'event_id' => $eventId,
                'message' => 'Callback event already received and processed. Duplicate safely ignored.',
            ];
        }

        
        try {
            return DB::transaction(function () use ($eventId, $providerReference, $eventType, $policyNumber, $payload) {
                
                $event = ProviderEvent::create([
                    'provider_event_id' => $eventId,
                    'provider_reference' => $providerReference,
                    'event_type' => $eventType,
                    'payload' => $payload,
                    'status' => ProviderEvent::STATUS_PROCESSED,
                    'processed_at' => now(),
                ]);

                
                
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
