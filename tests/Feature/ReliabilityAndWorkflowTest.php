<?php

namespace Tests\Feature;

use App\Models\MockInsurerPolicy;
use App\Models\PolicyRequest;
use App\Models\ProviderEvent;
use App\Services\MockInsurerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReliabilityAndWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function createReadyPolicy(array $attributes = []): PolicyRequest
    {
        return PolicyRequest::create(array_merge([
            'client_request_id' => 'POL-REQ-' . uniqid(),
            'agent_id' => 'AGENT-88',
            'customer_name' => 'Jane Smith',
            'date_of_birth' => '1990-05-15',
            'destination' => 'INTERNATIONAL',
            'trip_start' => '2026-12-01',
            'trip_end' => '2026-12-05',
            'plan_code' => 'PLUS',
            'sum_insured' => 300000,
            'travel_days' => 5,
            'age_at_trip_start' => 36,
            'base_daily_rate' => '20.00',
            'age_multiplier' => '1.00',
            'destination_multiplier' => '1.80',
            'coverage_multiplier' => '1.25',
            'calculated_premium' => '225.00',
            'status' => PolicyRequest::STATUS_RECEIVED,
            'scenario' => MockInsurerService::SCENARIO_SUCCESS,
        ], $attributes));
    }

    /**
     * Requirement:
     * Successful provider response produces exactly one policy number.
     * Repeated submit calls must NOT re-call provider or change policy number.
     */
    public function test_successful_provider_response_produces_exactly_one_policy_number_idempotently(): void
    {
        $policy = $this->createReadyPolicy(['scenario' => MockInsurerService::SCENARIO_SUCCESS]);

        // First submit call -> issues policy
        $response1 = $this->postJson("/api/policy-requests/{$policy->id}/submit");
        $response1->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'ISSUED',
            ]);

        $policyNumber1 = $response1->json('policy_number');
        $this->assertNotEmpty($policyNumber1);
        $this->assertEquals(PolicyRequest::STATUS_ISSUED, $policy->fresh()->status);
        $this->assertEquals($policyNumber1, $policy->fresh()->policy_number);

        // Second submit call -> Safe no-op! Returns existing policy without creating another policy number
        $response2 = $this->postJson("/api/policy-requests/{$policy->id}/submit");
        $response2->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'ISSUED',
                'policy_number' => $policyNumber1,
            ]);

        // Exactly one policy record exists in mock insurer datastore
        $this->assertEquals(1, MockInsurerPolicy::where('client_request_id', $policy->client_request_id)->count());
    }

    /**
     * Reliability Case B: Timeout after success
     * Scenario: Insurer creates policy internally, but client call times out.
     * Expected: PolicyFlow places request in NEEDS_RECONCILIATION. Does not blindly reissue.
     * Reconciles against provider status endpoint to restore ISSUED state safely.
     */
    public function test_timeout_after_success_is_flagged_for_reconciliation_and_resolved_without_double_issuance(): void
    {
        $policy = $this->createReadyPolicy(['scenario' => MockInsurerService::SCENARIO_TIMEOUT_AFTER_ISSUE]);

        // 1. Submit encounters simulated timeout
        $submitResponse = $this->postJson("/api/policy-requests/{$policy->id}/submit");
        $submitResponse->assertStatus(202)
            ->assertJson([
                'success' => false,
                'status' => 'NEEDS_RECONCILIATION',
            ]);

        $freshPolicy = $policy->fresh();
        $this->assertEquals(PolicyRequest::STATUS_NEEDS_RECONCILIATION, $freshPolicy->status);
        $this->assertNull($freshPolicy->policy_number, 'Policy number must remain unconfirmed until reconciliation');
        $this->assertNotNull($freshPolicy->provider_reference, 'Provider reference must be tracked for status inquiry');

        // Verify that insurer actually created policy in its internal store
        $upstreamPolicy = MockInsurerPolicy::where('provider_reference', $freshPolicy->provider_reference)->first();
        $this->assertNotNull($upstreamPolicy);
        $this->assertEquals('ISSUED', $upstreamPolicy->status);

        // 2. Reconciliation call checks provider status and resolves to ISSUED
        $reconcileResponse = $this->postJson("/api/policy-requests/{$policy->id}/reconcile");
        $reconcileResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'ISSUED',
                    'policy_number' => $upstreamPolicy->policy_number,
                ],
            ]);

        $this->assertEquals(PolicyRequest::STATUS_ISSUED, $policy->fresh()->status);
        $this->assertEquals($upstreamPolicy->policy_number, $policy->fresh()->policy_number);

        // Exactly one upstream policy exists (no double issuance)
        $this->assertEquals(1, MockInsurerPolicy::where('provider_reference', $freshPolicy->provider_reference)->count());
    }

    /**
     * Reliability Case C: Duplicate callback idempotency
     * Scenario: Insurer delivers identical event multiple times.
     * Expected: First delivery transitions state; subsequent identical deliveries are safely ignored.
     */
    public function test_duplicate_callback_is_applied_once_and_subsequent_events_are_safely_ignored(): void
    {
        // Setup a policy pending callback confirmation
        $policy = $this->createReadyPolicy([
            'status' => PolicyRequest::STATUS_PENDING_CONFIRMATION,
            'provider_reference' => 'REF-INS-CALLBACK-TEST',
        ]);

        $eventId = 'EVT-IDEMPOTENT-001';
        $policyNumber = 'POL-2026-CONFIRMED-99';

        $callbackPayload = [
            'event_id' => $eventId,
            'provider_reference' => 'REF-INS-CALLBACK-TEST',
            'event_type' => 'POLICY_ISSUED',
            'policy_number' => $policyNumber,
        ];

        // First callback delivery -> PROCESSED_ISSUED
        $response1 = $this->postJson('/api/mock-insurer/callback', $callbackPayload);
        $response1->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'PROCESSED_ISSUED',
                'policy_number' => $policyNumber,
            ]);

        $this->assertEquals(PolicyRequest::STATUS_ISSUED, $policy->fresh()->status);
        $this->assertEquals($policyNumber, $policy->fresh()->policy_number);

        // Second delivery of exact same callback -> IGNORED_DUPLICATE
        $response2 = $this->postJson('/api/mock-insurer/callback', $callbackPayload);
        $response2->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'IGNORED_DUPLICATE',
            ]);

        // Third delivery of exact same callback -> IGNORED_DUPLICATE
        $response3 = $this->postJson('/api/mock-insurer/callback', $callbackPayload);
        $response3->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'IGNORED_DUPLICATE',
            ]);

        // Database must have only 1 event recorded with this event_id
        $this->assertEquals(1, ProviderEvent::where('provider_event_id', $eventId)->count());
        $this->assertEquals(PolicyRequest::STATUS_ISSUED, $policy->fresh()->status);
    }

    /**
     * Requirement:
     * A repeated or delayed callback cannot overwrite a confirmed ISSUED state.
     */
    public function test_repeated_or_delayed_failure_callback_cannot_overwrite_confirmed_issued_state(): void
    {
        $policy = $this->createReadyPolicy([
            'status' => PolicyRequest::STATUS_ISSUED,
            'policy_number' => 'POL-SECURE-NEVER-OVERWRITE',
            'provider_reference' => 'REF-INS-TERMINAL-PROTECT',
        ]);

        // A malicious or delayed failure callback arrives for this policy
        $lateFailureCallback = [
            'event_id' => 'EVT-LATE-FAIL-001',
            'provider_reference' => 'REF-INS-TERMINAL-PROTECT',
            'event_type' => 'POLICY_FAILED',
            'reason' => 'Late underwriting rejection',
        ];

        $response = $this->postJson('/api/mock-insurer/callback', $lateFailureCallback);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'ALREADY_ISSUED_IGNORED',
            ]);

        // Policy MUST remain in ISSUED state with original policy number!
        $this->assertEquals(PolicyRequest::STATUS_ISSUED, $policy->fresh()->status);
        $this->assertEquals('POL-SECURE-NEVER-OVERWRITE', $policy->fresh()->policy_number);
    }

    /**
     * Reliability Case D: Retry after temporary failure
     * Scenario: Provider returns retriable failure. Engine transitions to FAILED.
     * On retry, submission resumes safely, uses original accepted premium snapshot,
     * and succeeds without creating duplicate records.
     */
    public function test_retry_after_temporary_failure_preserves_premium_and_issues_successfully(): void
    {
        $policy = $this->createReadyPolicy(['scenario' => MockInsurerService::SCENARIO_TEMPORARY_FAILURE]);
        $originalPremium = $policy->calculated_premium;

        // 1. Initial attempt fails due to upstream temporary failure
        $failResponse = $this->postJson("/api/policy-requests/{$policy->id}/submit");
        $failResponse->assertStatus(400)
            ->assertJson([
                'success' => false,
                'status' => 'FAILED',
            ]);

        $this->assertEquals(PolicyRequest::STATUS_FAILED, $policy->fresh()->status);
        $this->assertEquals(1, $policy->fresh()->attempt_count);

        // 2. Retry with SUCCESS scenario override
        $retryResponse = $this->postJson("/api/policy-requests/{$policy->id}/submit", [
            'scenario' => MockInsurerService::SCENARIO_SUCCESS,
        ]);

        $retryResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'ISSUED',
            ]);

        $this->assertEquals(PolicyRequest::STATUS_ISSUED, $policy->fresh()->status);
        $this->assertEquals(2, $policy->fresh()->attempt_count);
        $this->assertEquals($originalPremium, $policy->fresh()->calculated_premium, 'Original accepted premium must never change');
    }

    /**
     * Read endpoint: GET /api/policy-requests/{id} returns status, premium, and complete audit trail.
     */
    public function test_read_endpoint_returns_status_premium_and_complete_audit_trail(): void
    {
        $policy = $this->createReadyPolicy();
        $this->postJson("/api/policy-requests/{$policy->id}/submit");

        $response = $this->getJson("/api/policy-requests/{$policy->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'client_request_id',
                    'agent_id',
                    'customer_name',
                    'status',
                    'policy_number',
                    'provider_reference',
                    'pricing_breakdown' => [
                        'travel_days',
                        'age_at_trip_start',
                        'base_daily_rate',
                        'age_multiplier',
                        'destination_multiplier',
                        'coverage_multiplier',
                        'calculated_premium',
                        'currency',
                    ],
                    'audit_trail' => [
                        'state_history',
                        'attempts',
                    ],
                ],
            ]);
    }

    /**
     * List endpoint: GET /api/policy-requests with status filter and pagination.
     */
    public function test_list_endpoint_filters_by_status_and_paginates(): void
    {
        $this->createReadyPolicy(['status' => PolicyRequest::STATUS_ISSUED]);
        $this->createReadyPolicy(['status' => PolicyRequest::STATUS_FAILED]);

        $response = $this->getJson('/api/policy-requests?status=ISSUED');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'pagination' => ['current_page', 'per_page', 'total', 'last_page'],
            ]);

        foreach ($response->json('data') as $item) {
            $this->assertEquals('ISSUED', $item['status']);
        }
    }
}
