<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait HasTenantScope
{
    /**
     * Boot the trait.
     *
     * @return void
     */
    protected static function bootHasTenantScope()
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $user = Auth::user();
            
            if ($user && $user->tenant_id) {
                $builder->where('tenant_id', $user->tenant_id);
            }
        });
        
        // Automatically assign tenant_id when creating a new model
        static::creating(function ($model) {
            $user = Auth::user();
            
            if ($user && $user->tenant_id && !isset($model->tenant_id)) {
                $model->tenant_id = $user->tenant_id;
            }
        });
    }
    
    /**
     * Get the tenant that owns the model.
     */
    public function tenant()
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }
}
