<?php

namespace Tests\Unit;

use App\Exceptions\InvalidStateTransitionException;
use App\Models\PolicyRequest;
use App\Models\PolicyStateHistory;
use App\Services\PolicyStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyStateMachineTest extends TestCase
{
    use RefreshDatabase;

    private PolicyStateMachine $stateMachine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stateMachine = new PolicyStateMachine();
    }

    private function createDummyPolicy(string $status = PolicyRequest::STATUS_RECEIVED): PolicyRequest
    {
        return PolicyRequest::create([
            'client_request_id' => 'TEST-REQ-' . uniqid(),
            'agent_id' => 'AGENT-01',
            'customer_name' => 'Alice Tester',
            'date_of_birth' => '1995-01-01',
            'destination' => 'DOMESTIC',
            'trip_start' => '2026-06-01',
            'trip_end' => '2026-06-05',
            'plan_code' => 'BASIC',
            'sum_insured' => 100000,
            'travel_days' => 5,
            'age_at_trip_start' => 31,
            'base_daily_rate' => '12.00',
            'age_multiplier' => '1.00',
            'destination_multiplier' => '1.00',
            'coverage_multiplier' => '1.00',
            'calculated_premium' => '60.00',
            'status' => $status,
        ]);
    }

    public function test_can_transition_through_happy_path(): void
    {
        $policy = $this->createDummyPolicy(PolicyRequest::STATUS_RECEIVED);

        // 1. RECEIVED -> VALIDATED
        $this->stateMachine->transition($policy, PolicyRequest::STATUS_VALIDATED, 'VALIDATION', 'Inputs verified');
        $this->assertEquals(PolicyRequest::STATUS_VALIDATED, $policy->fresh()->status);

        // 2. VALIDATED -> SUBMITTED
        $this->stateMachine->transition($policy, PolicyRequest::STATUS_SUBMITTED, 'SUBMIT', 'Dispatched to insurer');
        $this->assertEquals(PolicyRequest::STATUS_SUBMITTED, $policy->fresh()->status);

        // 3. SUBMITTED -> ISSUED
        $this->stateMachine->transition($policy, PolicyRequest::STATUS_ISSUED, 'PROVIDER_SUCCESS', 'Insurer confirmed policy');
        $this->assertEquals(PolicyRequest::STATUS_ISSUED, $policy->fresh()->status);
        $this->assertNotNull($policy->fresh()->issued_at);

        // Audit trail should have 3 history rows
        $this->assertCount(3, PolicyStateHistory::where('policy_request_id', $policy->id)->get());
    }

    public function test_issued_state_cannot_be_overwritten_or_regressed(): void
    {
        $policy = $this->createDummyPolicy(PolicyRequest::STATUS_ISSUED);
        $policy->update(['policy_number' => 'POL-SECURE-999']);

        $this->expectException(InvalidStateTransitionException::class);
        $this->expectExceptionMessage('Policy is already ISSUED');

        // Attempt illegal regression ISSUED -> SUBMITTED
        $this->stateMachine->transition($policy, PolicyRequest::STATUS_SUBMITTED, 'ILLEGAL_REGRESSION');
    }

    public function test_cannot_skip_validation(): void
    {
        $policy = $this->createDummyPolicy(PolicyRequest::STATUS_RECEIVED);

        $this->expectException(InvalidStateTransitionException::class);
        $this->expectExceptionMessage('Illegal transition requested');

        // Attempt illegal skip RECEIVED -> SUBMITTED
        $this->stateMachine->transition($policy, PolicyRequest::STATUS_SUBMITTED, 'ATTEMPT_SKIP');
    }

    public function test_allows_safe_retry_from_failed_to_submitted(): void
    {
        $policy = $this->createDummyPolicy(PolicyRequest::STATUS_VALIDATED);
        $this->stateMachine->transition($policy, PolicyRequest::STATUS_SUBMITTED, 'SUBMIT');
        $this->stateMachine->transition($policy, PolicyRequest::STATUS_FAILED, 'INSURER_ERROR', 'Network connection timeout');

        $this->assertEquals(PolicyRequest::STATUS_FAILED, $policy->fresh()->status);

        // Retry must transition FAILED -> SUBMITTED
        $this->stateMachine->transition($policy, PolicyRequest::STATUS_SUBMITTED, 'MANUAL_RETRY', 'Retrying after failure');
        $this->assertEquals(PolicyRequest::STATUS_SUBMITTED, $policy->fresh()->status);
    }
}
