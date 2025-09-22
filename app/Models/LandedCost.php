<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use \App\Models\Traits\HasTenantScope;

class LandedCost extends Model
{
    use HasFactory, HasTenantScope;

    protected $primaryKey = 'landed_cost_id';

    protected $fillable = [
        'tenant_id',
        'costable_id',
        'costable_type',
        'description',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function costable(): MorphTo
    {
        return $this->morphTo();
    }
}