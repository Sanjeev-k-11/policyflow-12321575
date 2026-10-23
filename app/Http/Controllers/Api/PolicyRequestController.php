<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePolicyRequestRequest;
use App\Models\PolicyRequest;
use App\Models\PolicyStateHistory;
use App\Services\IssuanceEngine;
use App\Services\PricingService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PolicyRequestController extends Controller
{
    public function __construct(
        protected PricingService $pricingService,
        protected IssuanceEngine $issuanceEngine
    ) {}

    /**
     * Intake endpoint: Create a new policy request or safely return existing record.
     *
     * Concurrency & Reliability Case A: Duplicate intake prevention
     * - UNIQUE(agent_id, client_request_id) enforced in database.
     * - Returns 201 Created for a new request.
     * - Returns 200 OK for repeated submissions without duplicating records.
     * - Catches race conditions gracefully.
     */
    public function store(StorePolicyRequestRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $agentId = $validated['agent_id'];
        $clientRequestId = $validated['client_request_id'];

        // 1. Check if record already exists (fast path)
        $existing = PolicyRequest::where('agent_id', $agentId)
            ->where('client_request_id', $clientRequestId)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'is_duplicate' => true,
                'message' => 'Policy request already exists for this agent and client_request_id. Safely returning existing record.',
                'data' => $this->formatPolicyResponse($existing),
            ], 200);
        }

        // 2. Deterministic decimal-safe premium calculation
        $destination = strtoupper($validated['destination']);
        $planCode = strtoupper($validated['plan_code']);
        $sumInsured = (int) $validated['sum_insured'];

        $pricing = $this->pricingService->calculate(
            planCode: $planCode,
            sumInsured: $sumInsured,
            destination: $destination,
            dateOfBirth: $validated['date_of_birth'],
            tripStart: $validated['trip_start'],
            tripEnd: $validated['trip_end']
        );

        // 3. Atomic creation with race-condition catching
        try {
            $policy = DB::transaction(function () use ($validated, $destination, $planCode, $sumInsured, $pricing) {
                $policy = PolicyRequest::create([
                    'client_request_id' => $validated['client_request_id'],
                    'agent_id' => $validated['agent_id'],
                    'customer_name' => $validated['customer_name'],
                    'date_of_birth' => $validated['date_of_birth'],
                    'destination' => $destination,
                    'trip_start' => $validated['trip_start'],
                    'trip_end' => $validated['trip_end'],
                    'plan_code' => $planCode,
                    'sum_insured' => $sumInsured,

                    // Pricing snapshot: immutable breakdown
                    'travel_days' => $pricing['travel_days'],
                    'age_at_trip_start' => $pricing['age_at_trip_start'],
                    'base_daily_rate' => $pricing['base_daily_rate'],
                    'age_multiplier' => $pricing['age_multiplier'],
                    'destination_multiplier' => $pricing['destination_multiplier'],
                    'coverage_multiplier' => $pricing['coverage_multiplier'],
                    'calculated_premium' => $pricing['calculated_premium'],

                    'status' => PolicyRequest::STATUS_RECEIVED,
                    'scenario' => $validated['scenario'] ?? null,
                ]);

                // Initial state audit entry
                PolicyStateHistory::create([
                    'policy_request_id' => $policy->id,
                    'from_status' => null,
                    'to_status' => PolicyRequest::STATUS_RECEIVED,
                    'trigger' => 'INTAKE_API',
                    'reason' => 'Initial policy request intake validated and persisted',
                    'metadata' => [
                        'calculated_premium' => $pricing['calculated_premium'],
                        'travel_days' => $pricing['travel_days'],
                    ],
                    'created_at' => now(),
                ]);

                return $policy;
            });

            return response()->json([
                'success' => true,
                'is_duplicate' => false,
                'message' => 'Policy request received and priced successfully.',
                'data' => $this->formatPolicyResponse($policy),
            ], 201);

        } catch (QueryException $e) {
            // Concurrent race condition handler: two simultaneous requests bypassed the first check
            // and hit the database unique index. Catch the collision and return the winning record as 200 OK!
            if (str_contains($e->getMessage(), 'unique') || str_contains($e->getMessage(), '1062')) {
                $existing = PolicyRequest::where('agent_id', $agentId)
                    ->where('client_request_id', $clientRequestId)
                    ->firstOrFail();

                return response()->json([
                    'success' => true,
                    'is_duplicate' => true,
                    'message' => 'Concurrent duplicate intake detected and resolved. Safely returning existing record.',
                    'data' => $this->formatPolicyResponse($existing),
                ], 200);
            }

            throw $e;
        }
    }

    /**
     * Retrieve status, premium, references, and complete audit trail.
     */
    public function show(string $id): JsonResponse
    {
        $policy = PolicyRequest::where('id', $id)
            ->orWhere('client_request_id', $id)
            ->with(['attempts', 'stateHistory', 'providerEvents'])
            ->first();

        if (!$policy) {
            return response()->json([
                'success' => false,
                'message' => "Policy request [{$id}] not found.",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatPolicyResponse($policy, includeAudit: true),
        ], 200);
    }

    /**
     * Start or safely resume issuance.
     * Repeating this HTTP call will NOT produce duplicate policies or duplicate provider calls.
     */
    public function submit(Request $request, string $id): JsonResponse
    {
        $policy = PolicyRequest::where('id', $id)
            ->orWhere('client_request_id', $id)
            ->first();

        if (!$policy) {
            return response()->json([
                'success' => false,
                'message' => "Policy request [{$id}] not found.",
            ], 404);
        }

        $scenarioOverride = $request->input('scenario');
        $result = $this->issuanceEngine->submit($policy, $scenarioOverride);

        $statusCode = !empty($result['success']) ? 200 : (!empty($result['timeout']) ? 202 : 400);

        return response()->json([
            'success' => $result['success'] ?? false,
            'message' => $result['message'],
            'status' => $result['status'],
            'policy_number' => $result['policy_number'] ?? $policy->fresh()->policy_number,
            'provider_reference' => $result['provider_reference'] ?? $policy->fresh()->provider_reference,
            'data' => $this->formatPolicyResponse($policy->fresh(), includeAudit: true),
        ], $statusCode);
    }

    /**
     * Reconcile pending or timed-out request against mock insurer.
     */
    public function reconcile(Request $request, string $id): JsonResponse
    {
        $policy = PolicyRequest::where('id', $id)
            ->orWhere('client_request_id', $id)
            ->first();

        if (!$policy) {
            return response()->json([
                'success' => false,
                'message' => "Policy request [{$id}] not found.",
            ], 404);
        }

        $result = $this->issuanceEngine->reconcile($policy);

        return response()->json([
            'success' => $result['reconciled'] ?? false,
            'message' => $result['message'],
            'data' => $this->formatPolicyResponse($policy->fresh(), includeAudit: true),
            'details' => $result,
        ], 200);
    }

    /**
     * List requests with status filter and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = PolicyRequest::query()->orderBy('id', 'desc');

        if ($request->filled('status')) {
            $statuses = explode(',', $request->input('status'));
            $query->whereIn('status', array_map('trim', $statuses));
        }

        if ($request->filled('agent_id')) {
            $query->where('agent_id', $request->input('agent_id'));
        }

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('client_request_id', 'like', $search)
                  ->orWhere('customer_name', 'like', $search)
                  ->orWhere('policy_number', 'like', $search)
                  ->orWhere('provider_reference', 'like', $search);
            });
        }

        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
        $paginated = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $paginated->items(),
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ],
        ], 200);
    }

    /**
     * Helper to format consistent policy response structure.
     */
    protected function formatPolicyResponse(PolicyRequest $policy, bool $includeAudit = false): array
    {
        $data = [
            'id' => $policy->id,
            'client_request_id' => $policy->client_request_id,
            'agent_id' => $policy->agent_id,
            'customer_name' => $policy->customer_name,
            'date_of_birth' => $policy->date_of_birth->toDateString(),
            'destination' => $policy->destination,
            'trip_start' => $policy->trip_start->toDateString(),
            'trip_end' => $policy->trip_end->toDateString(),
            'plan_code' => $policy->plan_code,
            'sum_insured' => $policy->sum_insured,
            'status' => $policy->status,
            'policy_number' => $policy->policy_number,
            'provider_reference' => $policy->provider_reference,
            'attempt_count' => $policy->attempt_count,
            'failure_reason' => $policy->failure_reason,
            'issued_at' => $policy->issued_at?->toIso8601String(),
            'pricing_breakdown' => [
                'travel_days' => $policy->travel_days,
                'age_at_trip_start' => $policy->age_at_trip_start,
                'base_daily_rate' => $policy->base_daily_rate,
                'age_multiplier' => $policy->age_multiplier,
                'destination_multiplier' => $policy->destination_multiplier,
                'coverage_multiplier' => $policy->coverage_multiplier,
                'calculated_premium' => $policy->calculated_premium,
                'currency' => 'INR',
                'formula' => 'base_daily_rate × travel_days × age_multiplier × destination_multiplier × coverage_multiplier',
            ],
            'scenario' => $policy->scenario,
            'created_at' => $policy->created_at->toIso8601String(),
            'updated_at' => $policy->updated_at->toIso8601String(),
        ];

        if ($includeAudit) {
            $data['audit_trail'] = [
                'state_history' => $policy->stateHistory,
                'attempts' => $policy->attempts,
                'provider_events' => $policy->providerEvents,
            ];
        }

        return $data;
    }
}
