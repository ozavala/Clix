<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HasTenantScope;

class Payment extends Model
{
    use HasFactory, SoftDeletes, HasTenantScope;

    protected $primaryKey = 'payment_id';

    protected $fillable = [
        'tenant_id',
        'payable_id',
        'payable_type',
        'payment_date',
        'amount',
        'payment_method',
        'reference_number',
        'notes',
        'created_by_user_id',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->tenant_id)) {
                if (app()->bound('currentTenant') && app('currentTenant')) {
                    $model->tenant_id = app('currentTenant')->id ?? app('currentTenant')->tenant_id;
                } else if ($model->payable) {
                    $model->tenant_id = $model->payable->tenant_id ?? null;
                } else if (!empty($model->payable_type) && !empty($model->payable_id)) {
                    $class = $model->payable_type;
                    if (class_exists($class)) {
                        $parent = $class::find($model->payable_id);
                        if ($parent && isset($parent->tenant_id)) {
                            $model->tenant_id = $parent->tenant_id;
                        }
                    }
                }
            }
        });
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'created_by_user_id', 'user_id');
    }
}