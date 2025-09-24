<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HasTenantScope;

class Contact extends Model
{
    use HasFactory, SoftDeletes, HasTenantScope;

    protected $primaryKey = 'contact_id';

    protected $fillable = [
        'tenant_id',
        'contactable_id',
        'contactable_type',
        'first_name',
        'last_name',
        'email',
        'phone',
        'title',
        'created_by_user_id',
    ];

    /**
     * Get the parent contactable model (Customer or Supplier).
     */
    public function contactable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who created this contact.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'created_by_user_id', 'user_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->tenant_id)) {
                if (app()->bound('currentTenant') && app('currentTenant')) {
                    $model->tenant_id = app('currentTenant')->id ?? app('currentTenant')->tenant_id;
                } else {
                    // Try to infer from contactable parent
                    if (!empty($model->contactable_type) && !empty($model->contactable_id) && class_exists($model->contactable_type)) {
                        $parent = $model->contactable_type::find($model->contactable_id);
                        if ($parent && isset($parent->tenant_id)) {
                            $model->tenant_id = $parent->tenant_id;
                        }
                    }
                }
            }
        });
    }
}