<?php

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Relations\Pivot;

class CrmUserUserRole extends Pivot
{
    protected $table = 'crm_user_user_role';

    protected static function booted()
    {
        static::creating(function ($pivot) {
            // Auto-fill tenant_id if missing
            if (empty($pivot->tenant_id)) {
                // Try current tenant binding
                if (app()->bound('currentTenant') && app('currentTenant')) {
                    $pivot->tenant_id = app('currentTenant')->getKey();
                } else {
                    // Try resolving from user or role if available
                    if (!empty($pivot->user_id)) {
                        $user = \App\Models\CrmUser::find($pivot->user_id);
                        if ($user && $user->tenant_id) {
                            $pivot->tenant_id = $user->tenant_id;
                        }
                    }
                    if (empty($pivot->tenant_id) && !empty($pivot->role_id)) {
                        $role = \App\Models\UserRole::find($pivot->role_id);
                        if ($role && $role->tenant_id) {
                            $pivot->tenant_id = $role->tenant_id;
                        }
                    }
                }
            }
        });
    }
}
