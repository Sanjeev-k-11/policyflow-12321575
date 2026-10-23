<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderEvent extends Model
{
    use HasFactory;

    public const STATUS_PROCESSED = 'PROCESSED';
    public const STATUS_IGNORED_DUPLICATE = 'IGNORED_DUPLICATE';
    public const STATUS_REJECTED_REGRESSION = 'REJECTED_REGRESSION';
    public const STATUS_FAILED = 'FAILED';

    protected $guarded = ['id'];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];
}
