<?php

namespace App\Console\Commands;

use App\Models\PolicyRequest;
use App\Services\IssuanceEngine;
use Illuminate\Console\Command;

class ReconcileStalePoliciesCommand extends Command
{
    

    protected $signature = 'policy:reconcile-stale {--limit=50 : Maximum number of requests to reconcile}';

    

    protected $description = 'Detects stale PENDING_CONFIRMATION or NEEDS_RECONCILIATION policy requests and reconciles with mock provider';

    

    public function handle(IssuanceEngine $engine): int
    {
        $limit = (int) $this->option('limit');

        $this->info("Scanning for stale policy requests needing reconciliation (limit: {$limit})...");

        $candidates = PolicyRequest::whereIn('status', [
            PolicyRequest::STATUS_NEEDS_RECONCILIATION,
            PolicyRequest::STATUS_PENDING_CONFIRMATION,
        ])
        ->whereNotNull('provider_reference')
        ->limit($limit)
        ->get();

        if ($candidates->isEmpty()) {
            $this->info("✓ No stale requests found. All policies are up to date.");
            return self::SUCCESS;
        }

        $this->info("Found {$candidates->count()} request(s) to reconcile.");

        $rows = [];
        $reconciledCount = 0;

        foreach ($candidates as $policy) {
            $result = $engine->reconcile($policy);

            if (!empty($result['reconciled'])) {
                $reconciledCount++;
                $rows[] = [
                    $policy->id,
                    $policy->client_request_id,
                    $policy->provider_reference,
                    $result['policy_number'] ?? '—',
                    'RESOLVED (ISSUED)',
                ];
            } else {
                $rows[] = [
                    $policy->id,
                    $policy->client_request_id,
                    $policy->provider_reference,
                    '—',
                    $result['message'] ?? 'PENDING',
                ];
            }
        }

        $this->table(['ID', 'Client Request ID', 'Provider Ref', 'Policy Number', 'Outcome'], $rows);
        $this->info("Reconciliation complete. Successfully resolved {$reconciledCount} / {$candidates->count()} policy request(s).");

        return self::SUCCESS;
    }
}
