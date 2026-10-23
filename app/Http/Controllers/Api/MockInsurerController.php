<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MockInsurerPolicy;
use App\Services\IssuanceEngine;
use App\Services\MockInsurerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MockInsurerController extends Controller
{
    public function __construct(
        protected MockInsurerService $mockInsurer,
        protected IssuanceEngine $issuanceEngine
    ) {}

    /**
     * Simulated external insurer issuance endpoint.
     */
    public function issue(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'client_request_id' => 'required|string',
            'agent_id' => 'required|string',
            'customer_name' => 'required|string',
            'calculated_premium' => 'required|string',
            'scenario' => 'nullable|string',
        ]);

        $result = $this->mockInsurer->issue($payload, $payload['scenario'] ?? null);

        if (!empty($result['simulated_timeout'])) {
            return response()->json($result, 504);
        }

        if (empty($result['success'])) {
            return response()->json($result, 503);
        }

        return response()->json($result, 200);
    }

    /**
     * Simulated status lookup endpoint for reconciliation.
     * Expose GET /api/mock-insurer/status/{provider_reference}
     */
    public function status(string $provider_reference): JsonResponse
    {
        $status = $this->mockInsurer->getStatus($provider_reference);
        return response()->json($status, $status['found'] ? 200 : 404);
    }

    /**
     * Webhook callback receiver endpoint.
     * Receives simulated provider events.
     *
     * POST /api/mock-insurer/callback
     */
    public function callback(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'event_id' => 'required|string',
            'provider_reference' => 'required|string',
            'event_type' => 'nullable|string',
            'policy_number' => 'nullable|string',
            'reason' => 'nullable|string',
        ]);

        $result = $this->issuanceEngine->handleCallback($request->all());

        return response()->json($result, 200);
    }

    /**
     * Development helper endpoint: Simulates sending an identical callback 3 times
     * to demonstrate duplicate callback idempotency live in tests or review demo.
     */
    public function simulateDuplicateCallback(Request $request): JsonResponse
    {
        $providerReference = $request->input('provider_reference');
        $policyNumber = $request->input('policy_number', 'POL-' . date('Y') . '-' . strtoupper(Str::random(8)));
        $eventId = 'EVT-DUP-' . Str::upper(Str::random(10));

        $eventPayload = [
            'event_id' => $eventId,
            'provider_reference' => $providerReference,
            'event_type' => 'POLICY_ISSUED',
            'policy_number' => $policyNumber,
            'timestamp' => now()->toIso8601String(),
        ];

        // Deliver identical event 3 consecutive times
        $deliveryResults = [];
        for ($i = 1; $i <= 3; $i++) {
            $deliveryResults["delivery_#{$i}"] = $this->issuanceEngine->handleCallback($eventPayload);
        }

        return response()->json([
            'success' => true,
            'event_id' => $eventId,
            'message' => 'Simulated duplicate callback delivered 3 times to webhook engine.',
            'deliveries' => $deliveryResults,
        ], 200);
    }
}
