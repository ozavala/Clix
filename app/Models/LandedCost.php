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

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->tenant_id)) {
                if (app()->bound('currentTenant') && app('currentTenant')) {
                    $model->tenant_id = app('currentTenant')->id ?? app('currentTenant')->tenant_id;
                } elseif (!empty($model->costable_type) && !empty($model->costable_id) && class_exists($model->costable_type)) {
                    $parent = $model->costable_type::find($model->costable_id);
                    if ($parent && isset($parent->tenant_id)) {
                        $model->tenant_id = $parent->tenant_id;
                    }
                }
            }
        });
    }
}