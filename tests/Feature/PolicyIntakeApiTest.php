<?php

namespace Tests\Feature;

use App\Models\PolicyRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyIntakeApiTest extends TestCase
{
    use RefreshDatabase;

    private array $validPayload = [
        'client_request_id' => 'AGT-2026-0001',
        'agent_id' => 'AGENT-17',
        'customer_name' => 'Sample Customer',
        'date_of_birth' => '1998-04-10',
        'destination' => 'INTERNATIONAL',
        'trip_start' => '2026-11-10',
        'trip_end' => '2026-11-14',
        'plan_code' => 'PLUS',
        'sum_insured' => 300000,
    ];

    /**
     * Requirement:
     * POST /api/policy-requests with valid payload
     * Check: PLUS, 5 days, international, 300000, age 28 gives 225.00
     * Status code 201 Created
     */
    public function test_valid_request_produces_expected_premium_and_status(): void
    {
        $response = $this->postJson('/api/policy-requests', $this->validPayload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'is_duplicate' => false,
                'data' => [
                    'client_request_id' => 'AGT-2026-0001',
                    'agent_id' => 'AGENT-17',
                    'customer_name' => 'Sample Customer',
                    'status' => 'RECEIVED',
                    'pricing_breakdown' => [
                        'travel_days' => 5,
                        'age_at_trip_start' => 28,
                        'base_daily_rate' => '20.00',
                        'age_multiplier' => '1.00',
                        'destination_multiplier' => '1.80',
                        'coverage_multiplier' => '1.25',
                        'calculated_premium' => '225.00',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('policy_requests', [
            'client_request_id' => 'AGT-2026-0001',
            'agent_id' => 'AGENT-17',
            'status' => 'RECEIVED',
            'calculated_premium' => '225.00',
        ]);
    }

    /**
     * Requirement:
     * Repeated agent_id + client_request_id does not create another logical request.
     * Uses HTTP 201 for a newly created request, 200 for a safely repeated request.
     */
    public function test_repeated_submission_returns_200_and_does_not_duplicate(): void
    {
        // First submission -> 201 Created
        $firstResponse = $this->postJson('/api/policy-requests', $this->validPayload);
        $firstResponse->assertStatus(201);
        $firstId = $firstResponse->json('data.id');

        // Repeated submission with same agent_id and client_request_id -> 200 OK
        $secondResponse = $this->postJson('/api/policy-requests', $this->validPayload);
        $secondResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'is_duplicate' => true,
                'data' => [
                    'id' => $firstId,
                    'client_request_id' => 'AGT-2026-0001',
                    'agent_id' => 'AGENT-17',
                ],
            ]);

        // Database must strictly contain only 1 record
        $this->assertEquals(1, PolicyRequest::where('agent_id', 'AGENT-17')->where('client_request_id', 'AGT-2026-0001')->count());
    }

    /**
     * Requirement:
     * Invalid travel dates (trip_end < trip_start) must be rejected with 422.
     */
    public function test_invalid_travel_dates_are_rejected_with_422(): void
    {
        $payload = $this->validPayload;
        $payload['trip_start'] = '2026-11-20';
        $payload['trip_end'] = '2026-11-10'; // Invalid: ends before starts

        $response = $this->postJson('/api/policy-requests', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['trip_end']);
    }

    /**
     * Requirement:
     * Unsupported plan must be rejected with 422.
     */
    public function test_unsupported_plan_code_is_rejected_with_422(): void
    {
        $payload = $this->validPayload;
        $payload['plan_code'] = 'SUPER_VIP_DIAMOND';

        $response = $this->postJson('/api/policy-requests', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['plan_code']);
    }

    /**
     * Requirement:
     * Unsupported coverage sum_insured must be rejected with 422.
     */
    public function test_unsupported_sum_insured_is_rejected_with_422(): void
    {
        $payload = $this->validPayload;
        $payload['sum_insured'] = 999999;

        $response = $this->postJson('/api/policy-requests', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sum_insured']);
    }

    /**
     * Required fields validation.
     */
    public function test_missing_required_fields_return_meaningful_422_errors(): void
    {
        $response = $this->postJson('/api/policy-requests', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'client_request_id',
                'agent_id',
                'customer_name',
                'date_of_birth',
                'destination',
                'trip_start',
                'trip_end',
                'plan_code',
                'sum_insured',
            ]);
    }
}
