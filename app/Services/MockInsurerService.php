<?php

namespace App\Services;

use App\Models\MockInsurerPolicy;
use Illuminate\Support\Str;
use RuntimeException;

class MockInsurerService
{
    public const SCENARIO_SUCCESS = 'SUCCESS';
    public const SCENARIO_TEMPORARY_FAILURE = 'TEMPORARY_FAILURE';
    public const SCENARIO_TIMEOUT_AFTER_ISSUE = 'TIMEOUT_AFTER_ISSUE';
    public const SCENARIO_DELAYED_CALLBACK = 'DELAYED_CALLBACK';
    public const SCENARIO_DUPLICATE_CALLBACK = 'DUPLICATE_CALLBACK';

    public const ALL_SCENARIOS = [
        self::SCENARIO_SUCCESS,
        self::SCENARIO_TEMPORARY_FAILURE,
        self::SCENARIO_TIMEOUT_AFTER_ISSUE,
        self::SCENARIO_DELAYED_CALLBACK,
        self::SCENARIO_DUPLICATE_CALLBACK,
    ];

    

    public function issue(array $payload, ?string $scenarioOverride = null): array
    {
        $scenario = strtoupper($scenarioOverride ?: ($payload['scenario'] ?? self::SCENARIO_SUCCESS));
        $clientRequestId = $payload['client_request_id'] ?? 'REQ-' . Str::upper(Str::random(8));
        $agentId = $payload['agent_id'] ?? 'AGENT-01';
        $customerName = $payload['customer_name'] ?? 'Sample Customer';
        $premium = $payload['calculated_premium'] ?? '100.00';

        
        $refHash = strtoupper(substr(md5($agentId . ':' . $clientRequestId), 0, 10));
        $providerReference = "REF-INS-{$refHash}";

        
        $existing = MockInsurerPolicy::where('provider_reference', $providerReference)->first();
        if ($existing) {
            return [
                'success' => true,
                'status' => $existing->status,
                'policy_number' => $existing->policy_number,
                'provider_reference' => $existing->provider_reference,
                'scenario' => $scenario,
                'message' => 'Policy already exists in provider datastore',
                'raw' => $existing->toArray(),
            ];
        }

        switch ($scenario) {
            case self::SCENARIO_TEMPORARY_FAILURE:
                return [
                    'success' => false,
                    'status' => 'FAILED',
                    'error' => 'UPSTREAM_GATEWAY_TIMEOUT',
                    'retriable' => true,
                    'scenario' => $scenario,
                    'message' => 'External insurer gateway timeout. Retriable temporary failure.',
                    'provider_reference' => $providerReference,
                ];

            case self::SCENARIO_TIMEOUT_AFTER_ISSUE:
                
                
                $policyNumber = 'POL-' . date('Y') . '-' . strtoupper(Str::random(8));
                MockInsurerPolicy::create([
                    'provider_reference' => $providerReference,
                    'client_request_id' => $clientRequestId,
                    'agent_id' => $agentId,
                    'policy_number' => $policyNumber,
                    'status' => 'ISSUED',
                    'premium' => $premium,
                    'customer_name' => $customerName,
                    'metadata' => ['simulated_issue_time' => now()->toIso8601String()],
                ]);

                
                return [
                    'simulated_timeout' => true,
                    'success' => false,
                    'error' => 'CLIENT_CONNECTION_TIMEOUT',
                    'scenario' => $scenario,
                    'provider_reference' => $providerReference,
                    'message' => 'Simulated read timeout after remote issue: response was not received by client in time.',
                ];

            case self::SCENARIO_DELAYED_CALLBACK:
                
                return [
                    'success' => true,
                    'status' => 'PENDING',
                    'provider_reference' => $providerReference,
                    'scenario' => $scenario,
                    'message' => 'Request received and queued for underwriting. Result will be dispatched via callback.',
                ];

            case self::SCENARIO_DUPLICATE_CALLBACK:
            case self::SCENARIO_SUCCESS:
            default:
                $policyNumber = 'POL-' . date('Y') . '-' . strtoupper(Str::random(8));
                $policy = MockInsurerPolicy::create([
                    'provider_reference' => $providerReference,
                    'client_request_id' => $clientRequestId,
                    'agent_id' => $agentId,
                    'policy_number' => $policyNumber,
                    'status' => 'ISSUED',
                    'premium' => $premium,
                    'customer_name' => $customerName,
                    'metadata' => ['issued_at' => now()->toIso8601String()],
                ]);

                return [
                    'success' => true,
                    'status' => 'ISSUED',
                    'policy_number' => $policy->policy_number,
                    'provider_reference' => $providerReference,
                    'scenario' => $scenario,
                    'issued_at' => now()->toIso8601String(),
                    'message' => 'Policy issued successfully by mock provider.',
                ];
        }
    }

    

    public function getStatus(string $identifier): array
    {
        $policy = MockInsurerPolicy::where('provider_reference', $identifier)
            ->orWhere('client_request_id', $identifier)
            ->first();

        if (!$policy) {
            return [
                'found' => false,
                'provider_reference' => $identifier,
                'status' => 'NOT_FOUND',
                'message' => 'No policy record found at insurer for reference',
            ];
        }

        return [
            'found' => true,
            'provider_reference' => $policy->provider_reference,
            'status' => $policy->status,
            'policy_number' => $policy->policy_number,
            'customer_name' => $policy->customer_name,
            'premium' => (string) $policy->premium,
            'created_at' => $policy->created_at->toIso8601String(),
        ];
    }
}
