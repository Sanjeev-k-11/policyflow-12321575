<?php

namespace App\Services;

use App\Exceptions\InvalidStateTransitionException;
use App\Models\PolicyRequest;
use App\Models\PolicyStateHistory;
use Illuminate\Support\Facades\DB;

class PolicyStateMachine
{
    

    private const ALLOWED_TRANSITIONS = [
        PolicyRequest::STATUS_RECEIVED => [
            PolicyRequest::STATUS_VALIDATED,
            PolicyRequest::STATUS_FAILED,
        ],
        PolicyRequest::STATUS_VALIDATED => [
            PolicyRequest::STATUS_SUBMITTED,
            PolicyRequest::STATUS_FAILED,
        ],
        PolicyRequest::STATUS_SUBMITTED => [
            PolicyRequest::STATUS_ISSUED,
            PolicyRequest::STATUS_PENDING_CONFIRMATION,
            PolicyRequest::STATUS_NEEDS_RECONCILIATION,
            PolicyRequest::STATUS_FAILED,
        ],
        PolicyRequest::STATUS_PENDING_CONFIRMATION => [
            PolicyRequest::STATUS_ISSUED,
            PolicyRequest::STATUS_NEEDS_RECONCILIATION,
            PolicyRequest::STATUS_FAILED,
        ],
        PolicyRequest::STATUS_NEEDS_RECONCILIATION => [
            PolicyRequest::STATUS_ISSUED,
            PolicyRequest::STATUS_PENDING_CONFIRMATION,
            PolicyRequest::STATUS_FAILED,
        ],
        PolicyRequest::STATUS_FAILED => [
            
            PolicyRequest::STATUS_SUBMITTED,
        ],
        
        PolicyRequest::STATUS_ISSUED => [],
    ];

    

    public function canTransition(string $from, string $to): bool
    {
        if ($from === $to) {
            return false;
        }

        $allowed = self::ALLOWED_TRANSITIONS[$from] ?? [];
        return in_array($to, $allowed, true);
    }

    

    public function transition(
        PolicyRequest $policy,
        string $toStatus,
        string $trigger,
        ?string $reason = null,
        ?array $metadata = null
    ): PolicyRequest {
        $fromStatus = $policy->status;

        
        if ($fromStatus === PolicyRequest::STATUS_ISSUED) {
            throw new InvalidStateTransitionException(
                $fromStatus,
                $toStatus,
                "Policy is already ISSUED with number [{$policy->policy_number}]. Confirmed policies cannot be mutated or regressed."
            );
        }

        
        if (!$this->canTransition($fromStatus, $toStatus)) {
            throw new InvalidStateTransitionException(
                $fromStatus,
                $toStatus,
                "Illegal transition requested by trigger [{$trigger}]"
            );
        }

        
        return DB::transaction(function () use ($policy, $fromStatus, $toStatus, $trigger, $reason, $metadata) {
            $policy->status = $toStatus;

            if ($toStatus === PolicyRequest::STATUS_ISSUED && empty($policy->issued_at)) {
                $policy->issued_at = now();
            }

            if ($toStatus === PolicyRequest::STATUS_FAILED && $reason) {
                $policy->failure_reason = $reason;
            }

            $policy->save();

            
            PolicyStateHistory::create([
                'policy_request_id' => $policy->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'trigger' => $trigger,
                'reason' => $reason,
                'metadata' => $metadata,
                'created_at' => now(),
            ]);

            return $policy;
        });
    }
}
