<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PolicyRequest extends Model
{
    use HasFactory;

    // Supported lifecycle states
    public const STATUS_RECEIVED = 'RECEIVED';
    public const STATUS_VALIDATED = 'VALIDATED';
    public const STATUS_SUBMITTED = 'SUBMITTED';
    public const STATUS_PENDING_CONFIRMATION = 'PENDING_CONFIRMATION';
    public const STATUS_ISSUED = 'ISSUED';
    public const STATUS_FAILED = 'FAILED';
    public const STATUS_NEEDS_RECONCILIATION = 'NEEDS_RECONCILIATION';

    public const ALL_STATUSES = [
        self::STATUS_RECEIVED,
        self::STATUS_VALIDATED,
        self::STATUS_SUBMITTED,
        self::STATUS_PENDING_CONFIRMATION,
        self::STATUS_ISSUED,
        self::STATUS_FAILED,
        self::STATUS_NEEDS_RECONCILIATION,
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'date_of_birth' => 'date:Y-m-d',
        'trip_start' => 'date:Y-m-d',
        'trip_end' => 'date:Y-m-d',
        'sum_insured' => 'integer',
        'travel_days' => 'integer',
        'age_at_trip_start' => 'integer',
        'base_daily_rate' => 'decimal:2',
        'age_multiplier' => 'decimal:2',
        'destination_multiplier' => 'decimal:2',
        'coverage_multiplier' => 'decimal:2',
        'calculated_premium' => 'decimal:2',
        'last_response' => 'array',
        'attempt_count' => 'integer',
        'issued_at' => 'datetime',
    ];

    public function attempts(): HasMany
    {
        return $this->hasMany(IssuanceAttempt::class, 'policy_request_id')->orderBy('attempt_number', 'asc');
    }

    public function stateHistory(): HasMany
    {
        return $this->hasMany(PolicyStateHistory::class, 'policy_request_id')->orderBy('id', 'asc');
    }

    public function providerEvents(): HasMany
    {
        return $this->hasMany(ProviderEvent::class, 'provider_reference', 'provider_reference')->orderBy('id', 'asc');
    }

    public function isIssued(): bool
    {
        return $this->status === self::STATUS_ISSUED;
    }

    public function canSubmit(): bool
    {
        return in_array($this->status, [
            self::STATUS_RECEIVED,
            self::STATUS_VALIDATED,
            self::STATUS_FAILED,
        ], true);
    }

    public function canReconcile(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING_CONFIRMATION,
            self::STATUS_NEEDS_RECONCILIATION,
            self::STATUS_SUBMITTED,
        ], true) && !empty($this->provider_reference);
    }
}
