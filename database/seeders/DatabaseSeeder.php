<?php

namespace Database\Seeders;

use App\Models\IssuanceAttempt;
use App\Models\MockInsurerPolicy;
use App\Models\PolicyRequest;
use App\Models\PolicyStateHistory;
use App\Models\ProviderEvent;
use App\Services\MockInsurerService;
use App\Services\PricingService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $pricing = new PricingService();

        $candidates = [
            // 1-8: ISSUED Policies
            [
                'client_request_id' => 'AGT-2026-0001',
                'agent_id' => 'AGENT-17',
                'customer_name' => 'Sample Customer',
                'dob' => '1998-04-10',
                'dest' => 'INTERNATIONAL',
                'start' => '2026-11-10',
                'end' => '2026-11-14',
                'plan' => 'PLUS',
                'sum' => 300000,
                'status' => PolicyRequest::STATUS_ISSUED,
                'policy_num' => 'POL-2026-IN001',
            ],
            [
                'client_request_id' => 'AGT-2026-0002',
                'agent_id' => 'AGENT-17',
                'customer_name' => 'Aarav Mehta',
                'dob' => '1985-06-20',
                'dest' => 'DOMESTIC',
                'start' => '2026-10-01',
                'end' => '2026-10-08',
                'plan' => 'BASIC',
                'sum' => 100000,
                'status' => PolicyRequest::STATUS_ISSUED,
                'policy_num' => 'POL-2026-DM002',
            ],
            [
                'client_request_id' => 'AGT-2026-0003',
                'agent_id' => 'AGENT-09',
                'customer_name' => 'Pooja Iyer',
                'dob' => '1975-12-11',
                'dest' => 'INTERNATIONAL',
                'start' => '2026-12-15',
                'end' => '2026-12-25',
                'plan' => 'PREMIUM',
                'sum' => 500000,
                'status' => PolicyRequest::STATUS_ISSUED,
                'policy_num' => 'POL-2026-IN003',
            ],
            [
                'client_request_id' => 'AGT-2026-0004',
                'agent_id' => 'AGENT-09',
                'customer_name' => 'Rohan Varma',
                'dob' => '1962-03-05',
                'dest' => 'INTERNATIONAL',
                'start' => '2026-11-05',
                'end' => '2026-11-12',
                'plan' => 'PLUS',
                'sum' => 300000,
                'status' => PolicyRequest::STATUS_ISSUED,
                'policy_num' => 'POL-2026-IN004',
            ],
            [
                'client_request_id' => 'AGT-2026-0005',
                'agent_id' => 'AGENT-42',
                'customer_name' => 'Ananya Sen',
                'dob' => '2010-08-14',
                'dest' => 'DOMESTIC',
                'start' => '2026-10-15',
                'end' => '2026-10-18',
                'plan' => 'BASIC',
                'sum' => 100000,
                'status' => PolicyRequest::STATUS_ISSUED,
                'policy_num' => 'POL-2026-DM005',
            ],
            [
                'client_request_id' => 'AGT-2026-0006',
                'agent_id' => 'AGENT-42',
                'customer_name' => 'Vikram Singhania',
                'dob' => '1950-02-18',
                'dest' => 'INTERNATIONAL',
                'start' => '2026-12-01',
                'end' => '2026-12-10',
                'plan' => 'PREMIUM',
                'sum' => 500000,
                'status' => PolicyRequest::STATUS_ISSUED,
                'policy_num' => 'POL-2026-IN006',
            ],
            [
                'client_request_id' => 'AGT-2026-0007',
                'agent_id' => 'AGENT-12',
                'customer_name' => 'Neha Deshmukh',
                'dob' => '1992-09-09',
                'dest' => 'DOMESTIC',
                'start' => '2026-11-01',
                'end' => '2026-11-04',
                'plan' => 'PLUS',
                'sum' => 300000,
                'status' => PolicyRequest::STATUS_ISSUED,
                'policy_num' => 'POL-2026-DM007',
            ],
            [
                'client_request_id' => 'AGT-2026-0008',
                'agent_id' => 'AGENT-12',
                'customer_name' => 'Kunal Kapoor',
                'dob' => '1988-11-30',
                'dest' => 'INTERNATIONAL',
                'start' => '2026-12-20',
                'end' => '2026-12-28',
                'plan' => 'BASIC',
                'sum' => 100000,
                'status' => PolicyRequest::STATUS_ISSUED,
                'policy_num' => 'POL-2026-IN008',
            ],

            // 9-12: PENDING_CONFIRMATION
            [
                'client_request_id' => 'AGT-2026-0009',
                'agent_id' => 'AGENT-17',
                'customer_name' => 'Suresh Nair',
                'dob' => '1980-05-12',
                'dest' => 'INTERNATIONAL',
                'start' => '2026-11-25',
                'end' => '2026-12-02',
                'plan' => 'PLUS',
                'sum' => 300000,
                'status' => PolicyRequest::STATUS_PENDING_CONFIRMATION,
            ],
            [
                'client_request_id' => 'AGT-2026-0010',
                'agent_id' => 'AGENT-21',
                'customer_name' => 'Fatima Sheikh',
                'dob' => '1994-01-22',
                'dest' => 'DOMESTIC',
                'start' => '2026-11-18',
                'end' => '2026-11-22',
                'plan' => 'BASIC',
                'sum' => 100000,
                'status' => PolicyRequest::STATUS_PENDING_CONFIRMATION,
            ],
            [
                'client_request_id' => 'AGT-2026-0011',
                'agent_id' => 'AGENT-03',
                'customer_name' => 'Rajesh Joshi',
                'dob' => '1965-07-19',
                'dest' => 'INTERNATIONAL',
                'start' => '2026-12-05',
                'end' => '2026-12-14',
                'plan' => 'PREMIUM',
                'sum' => 500000,
                'status' => PolicyRequest::STATUS_PENDING_CONFIRMATION,
            ],
            [
                'client_request_id' => 'AGT-2026-0012',
                'agent_id' => 'AGENT-03',
                'customer_name' => 'Meera Pillai',
                'dob' => '2001-10-10',
                'dest' => 'INTERNATIONAL',
                'start' => '2026-11-15',
                'end' => '2026-11-20',
                'plan' => 'PLUS',
                'sum' => 300000,
                'status' => PolicyRequest::STATUS_PENDING_CONFIRMATION,
            ],

            // 13-16: NEEDS_RECONCILIATION
            [
                'client_request_id' => 'AGT-2026-0013',
                'agent_id' => 'AGENT-55',
                'customer_name' => 'Devendra Patel',
                'dob' => '1978-04-03',
                'dest' => 'INTERNATIONAL',
                'start' => '2026-11-08',
                'end' => '2026-11-15',
                'plan' => 'PREMIUM',
                'sum' => 500000,
                'status' => PolicyRequest::STATUS_NEEDS_RECONCILIATION,
                'scenario' => MockInsurerService::SCENARIO_TIMEOUT_AFTER_ISSUE,
            ],
            [
                'client_request_id' => 'AGT-2026-0014',
                'agent_id' => 'AGENT-55',
                'customer_name' => 'Simran Kaur',
                'dob' => '1996-08-25',
                'dest' => 'DOMESTIC',
                'start' => '2026-10-28',
                'end' => '2026-11-02',
                'plan' => 'PLUS',
                'sum' => 300000,
                'status' => PolicyRequest::STATUS_NEEDS_RECONCILIATION,
                'scenario' => MockInsurerService::SCENARIO_TIMEOUT_AFTER_ISSUE,
            ],
            [
                'client_request_id' => 'AGT-2026-0015',
                'agent_id' => 'AGENT-19',
                'customer_name' => 'Deepak Chopra',
                'dob' => '1955-12-01',
                'dest' => 'INTERNATIONAL',
                'start' => '2026-12-10',
                'end' => '2026-12-18',
                'plan' => 'PLUS',
                'sum' => 300000,
                'status' => PolicyRequest::STATUS_NEEDS_RECONCILIATION,
                'scenario' => MockInsurerService::SCENARIO_TIMEOUT_AFTER_ISSUE,
            ],

            // 17-20: FAILED (retriable or permanent)
            [
                'client_request_id' => 'AGT-2026-0016',
                'agent_id' => 'AGENT-88',
                'customer_name' => 'Harish Chandra',
                'dob' => '1982-02-14',
                'dest' => 'INTERNATIONAL',
                'start' => '2026-11-02',
                'end' => '2026-11-07',
                'plan' => 'PLUS',
                'sum' => 300000,
                'status' => PolicyRequest::STATUS_FAILED,
                'failure_reason' => 'Upstream gateway error 504. External insurer timeout.',
                'scenario' => MockInsurerService::SCENARIO_TEMPORARY_FAILURE,
            ],
            [
                'client_request_id' => 'AGT-2026-0017',
                'agent_id' => 'AGENT-88',
                'customer_name' => 'Kavita Menon',
                'dob' => '1999-07-07',
                'dest' => 'DOMESTIC',
                'start' => '2026-10-25',
                'end' => '2026-10-29',
                'plan' => 'BASIC',
                'sum' => 100000,
                'status' => PolicyRequest::STATUS_FAILED,
                'failure_reason' => 'Insurer underwriting declined: sanctions list match.',
            ],
            [
                'client_request_id' => 'AGT-2026-0018',
                'agent_id' => 'AGENT-31',
                'customer_name' => 'Mohit Agarwal',
                'dob' => '1991-03-31',
                'dest' => 'INTERNATIONAL',
                'start' => '2026-11-12',
                'end' => '2026-11-19',
                'plan' => 'PREMIUM',
                'sum' => 500000,
                'status' => PolicyRequest::STATUS_FAILED,
                'failure_reason' => 'Network socket reset during handshake.',
                'scenario' => MockInsurerService::SCENARIO_TEMPORARY_FAILURE,
            ],

            // 21-22: SUBMITTED (in-flight)
            [
                'client_request_id' => 'AGT-2026-0019',
                'agent_id' => 'AGENT-07',
                'customer_name' => 'Tanvi Bakshi',
                'dob' => '1997-11-11',
                'dest' => 'INTERNATIONAL',
                'start' => '2026-12-01',
                'end' => '2026-12-06',
                'plan' => 'PLUS',
                'sum' => 300000,
                'status' => PolicyRequest::STATUS_SUBMITTED,
            ],
            [
                'client_request_id' => 'AGT-2026-0020',
                'agent_id' => 'AGENT-07',
                'customer_name' => 'Gaurav Kulkarni',
                'dob' => '1989-05-05',
                'dest' => 'DOMESTIC',
                'start' => '2026-11-20',
                'end' => '2026-11-25',
                'plan' => 'BASIC',
                'sum' => 100000,
                'status' => PolicyRequest::STATUS_SUBMITTED,
            ],

            // 23-24: VALIDATED
            [
                'client_request_id' => 'AGT-2026-0021',
                'agent_id' => 'AGENT-14',
                'customer_name' => 'Divya Ranganathan',
                'dob' => '1993-01-15',
                'dest' => 'DOMESTIC',
                'start' => '2026-11-10',
                'end' => '2026-11-16',
                'plan' => 'PLUS',
                'sum' => 300000,
                'status' => PolicyRequest::STATUS_VALIDATED,
            ],
            [
                'client_request_id' => 'AGT-2026-0022',
                'agent_id' => 'AGENT-14',
                'customer_name' => 'Sunil Reddy',
                'dob' => '1970-10-04',
                'dest' => 'INTERNATIONAL',
                'start' => '2026-12-14',
                'end' => '2026-12-22',
                'plan' => 'PREMIUM',
                'sum' => 500000,
                'status' => PolicyRequest::STATUS_VALIDATED,
            ],

            // 25-26: RECEIVED
            [
                'client_request_id' => 'AGT-2026-0023',
                'agent_id' => 'AGENT-99',
                'customer_name' => 'Ishita Chatterjee',
                'dob' => '2002-09-18',
                'dest' => 'INTERNATIONAL',
                'start' => '2026-11-28',
                'end' => '2026-12-05',
                'plan' => 'PLUS',
                'sum' => 300000,
                'status' => PolicyRequest::STATUS_RECEIVED,
            ],
            [
                'client_request_id' => 'AGT-2026-0024',
                'agent_id' => 'AGENT-99',
                'customer_name' => 'Pranav Mukherjee',
                'dob' => '1984-12-24',
                'dest' => 'DOMESTIC',
                'start' => '2026-12-01',
                'end' => '2026-12-04',
                'plan' => 'BASIC',
                'sum' => 100000,
                'status' => PolicyRequest::STATUS_RECEIVED,
            ],
        ];

        foreach ($candidates as $item) {
            $calc = $pricing->calculate(
                planCode: $item['plan'],
                sumInsured: $item['sum'],
                destination: $item['dest'],
                dateOfBirth: $item['dob'],
                tripStart: $item['start'],
                tripEnd: $item['end']
            );

            $refHash = strtoupper(substr(md5($item['agent_id'] . ':' . $item['client_request_id']), 0, 10));
            $providerRef = "REF-INS-{$refHash}";
            $policyNumber = $item['policy_num'] ?? ($item['status'] === PolicyRequest::STATUS_ISSUED ? 'POL-2026-' . strtoupper(Str::random(6)) : null);

            $policy = PolicyRequest::create([
                'client_request_id' => $item['client_request_id'],
                'agent_id' => $item['agent_id'],
                'customer_name' => $item['customer_name'],
                'date_of_birth' => $item['dob'],
                'destination' => $item['dest'],
                'trip_start' => $item['start'],
                'trip_end' => $item['end'],
                'plan_code' => $item['plan'],
                'sum_insured' => $item['sum'],

                'travel_days' => $calc['travel_days'],
                'age_at_trip_start' => $calc['age_at_trip_start'],
                'base_daily_rate' => $calc['base_daily_rate'],
                'age_multiplier' => $calc['age_multiplier'],
                'destination_multiplier' => $calc['destination_multiplier'],
                'coverage_multiplier' => $calc['coverage_multiplier'],
                'calculated_premium' => $calc['calculated_premium'],

                'status' => $item['status'],
                'policy_number' => $policyNumber,
                'provider_reference' => in_array($item['status'], [
                    PolicyRequest::STATUS_SUBMITTED,
                    PolicyRequest::STATUS_PENDING_CONFIRMATION,
                    PolicyRequest::STATUS_ISSUED,
                    PolicyRequest::STATUS_NEEDS_RECONCILIATION,
                    PolicyRequest::STATUS_FAILED,
                ]) ? $providerRef : null,
                'attempt_count' => in_array($item['status'], [PolicyRequest::STATUS_RECEIVED, PolicyRequest::STATUS_VALIDATED]) ? 0 : 1,
                'failure_reason' => $item['failure_reason'] ?? null,
                'scenario' => $item['scenario'] ?? null,
                'issued_at' => $item['status'] === PolicyRequest::STATUS_ISSUED ? now()->subHours(rand(1, 48)) : null,
                'created_at' => now()->subHours(rand(2, 72)),
            ]);

            // If ISSUED or TIMEOUT_AFTER_ISSUE, populate upstream insurer record
            if ($item['status'] === PolicyRequest::STATUS_ISSUED) {
                MockInsurerPolicy::firstOrCreate(
                    ['provider_reference' => $providerRef],
                    [
                        'client_request_id' => $item['client_request_id'],
                        'agent_id' => $item['agent_id'],
                        'policy_number' => $policyNumber,
                        'status' => 'ISSUED',
                        'premium' => $calc['calculated_premium'],
                        'customer_name' => $item['customer_name'],
                    ]
                );
            } elseif ($item['status'] === PolicyRequest::STATUS_NEEDS_RECONCILIATION) {
                // Simulate upstream insurer has issued policy, but local PolicyFlow timed out
                MockInsurerPolicy::firstOrCreate(
                    ['provider_reference' => $providerRef],
                    [
                        'client_request_id' => $item['client_request_id'],
                        'agent_id' => $item['agent_id'],
                        'policy_number' => 'POL-RECON-' . strtoupper(Str::random(6)),
                        'status' => 'ISSUED',
                        'premium' => $calc['calculated_premium'],
                        'customer_name' => $item['customer_name'],
                    ]
                );
            } elseif ($item['status'] === PolicyRequest::STATUS_PENDING_CONFIRMATION) {
                // Upstream insurer completed underwriting; ready for reconciliation
                MockInsurerPolicy::firstOrCreate(
                    ['provider_reference' => $providerRef],
                    [
                        'client_request_id' => $item['client_request_id'],
                        'agent_id' => $item['agent_id'],
                        'policy_number' => 'POL-ASYNC-' . strtoupper(Str::random(6)),
                        'status' => 'ISSUED',
                        'premium' => $calc['calculated_premium'],
                        'customer_name' => $item['customer_name'],
                    ]
                );
            }

            // Record initial state history: INTAKE -> RECEIVED
            PolicyStateHistory::create([
                'policy_request_id' => $policy->id,
                'from_status' => null,
                'to_status' => PolicyRequest::STATUS_RECEIVED,
                'trigger' => 'INTAKE_API',
                'reason' => 'Request received and immutable premium computed',
                'created_at' => $policy->created_at,
            ]);

            // Add lifecycle transitions
            if ($item['status'] !== PolicyRequest::STATUS_RECEIVED) {
                PolicyStateHistory::create([
                    'policy_request_id' => $policy->id,
                    'from_status' => PolicyRequest::STATUS_RECEIVED,
                    'to_status' => PolicyRequest::STATUS_VALIDATED,
                    'trigger' => 'VALIDATION_ENGINE',
                    'reason' => 'Pre-issuance validation verified',
                    'created_at' => $policy->created_at->addSeconds(1),
                ]);
            }

            if (in_array($item['status'], [
                PolicyRequest::STATUS_SUBMITTED,
                PolicyRequest::STATUS_PENDING_CONFIRMATION,
                PolicyRequest::STATUS_ISSUED,
                PolicyRequest::STATUS_NEEDS_RECONCILIATION,
                PolicyRequest::STATUS_FAILED,
            ])) {
                PolicyStateHistory::create([
                    'policy_request_id' => $policy->id,
                    'from_status' => PolicyRequest::STATUS_VALIDATED,
                    'to_status' => PolicyRequest::STATUS_SUBMITTED,
                    'trigger' => 'SUBMIT_DISPATCH',
                    'reason' => 'Attempt #1 dispatched to external insurer',
                    'created_at' => $policy->created_at->addSeconds(2),
                ]);

                // Create attempt record
                IssuanceAttempt::create([
                    'policy_request_id' => $policy->id,
                    'attempt_number' => 1,
                    'idempotency_key' => "IDEM-POL{$policy->id}-ATTEMPT-1",
                    'scenario' => $item['scenario'] ?? 'SUCCESS',
                    'status' => $item['status'] === PolicyRequest::STATUS_ISSUED ? 'SUCCESS' : ($item['status'] === PolicyRequest::STATUS_NEEDS_RECONCILIATION ? 'TIMEOUT' : ($item['status'] === PolicyRequest::STATUS_FAILED ? 'FAILED' : 'PENDING')),
                    'duration_ms' => rand(150, 650),
                    'created_at' => $policy->created_at->addSeconds(2),
                ]);
            }

            if ($item['status'] === PolicyRequest::STATUS_ISSUED) {
                PolicyStateHistory::create([
                    'policy_request_id' => $policy->id,
                    'from_status' => PolicyRequest::STATUS_SUBMITTED,
                    'to_status' => PolicyRequest::STATUS_ISSUED,
                    'trigger' => 'INSURER_CONFIRMATION',
                    'reason' => "Insurer issued policy [{$policyNumber}]",
                    'created_at' => $policy->created_at->addSeconds(3),
                ]);
            } elseif ($item['status'] === PolicyRequest::STATUS_PENDING_CONFIRMATION) {
                PolicyStateHistory::create([
                    'policy_request_id' => $policy->id,
                    'from_status' => PolicyRequest::STATUS_SUBMITTED,
                    'to_status' => PolicyRequest::STATUS_PENDING_CONFIRMATION,
                    'trigger' => 'INSURER_ACK',
                    'reason' => 'Async underwriting underway',
                    'created_at' => $policy->created_at->addSeconds(3),
                ]);
            } elseif ($item['status'] === PolicyRequest::STATUS_NEEDS_RECONCILIATION) {
                PolicyStateHistory::create([
                    'policy_request_id' => $policy->id,
                    'from_status' => PolicyRequest::STATUS_SUBMITTED,
                    'to_status' => PolicyRequest::STATUS_NEEDS_RECONCILIATION,
                    'trigger' => 'INSURER_TIMEOUT',
                    'reason' => 'Simulated timeout occurred; reconciliation required',
                    'created_at' => $policy->created_at->addSeconds(3),
                ]);
            } elseif ($item['status'] === PolicyRequest::STATUS_FAILED) {
                PolicyStateHistory::create([
                    'policy_request_id' => $policy->id,
                    'from_status' => PolicyRequest::STATUS_SUBMITTED,
                    'to_status' => PolicyRequest::STATUS_FAILED,
                    'trigger' => 'INSURER_REJECTION',
                    'reason' => $item['failure_reason'],
                    'created_at' => $policy->created_at->addSeconds(3),
                ]);
            }
        }
    }
}
