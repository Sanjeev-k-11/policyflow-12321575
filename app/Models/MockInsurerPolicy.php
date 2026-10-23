<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MockInsurerPolicy extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'premium' => 'decimal:2',
        'metadata' => 'array',
    ];
}
