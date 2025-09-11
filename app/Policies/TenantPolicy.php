<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TenantPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view-any-tenant');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Tenant $tenant): bool
    {
        return $user->can('view-tenant') && 
               ($user->tenant_id === $tenant->id || $user->can('view-all-tenants'));
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create-tenant');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Tenant $tenant): bool
    {
        return $user->can('update-tenant') && 
               ($user->tenant_id === $tenant->id || $user->can('update-any-tenant'));
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Tenant $tenant): bool
    {
        // Prevent deleting the current tenant
        if ($user->tenant_id === $tenant->id) {
            return false;
        }
        
        return $user->can('delete-tenant') && 
               $user->can('delete-any-tenant');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Tenant $tenant): bool
    {
        return $user->can('restore-tenant') && 
               ($user->tenant_id === $tenant->id || $user->can('restore-any-tenant'));
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Tenant $tenant): bool
    {
        // Prevent force deleting the current tenant
        if ($user->tenant_id === $tenant->id) {
            return false;
        }
        
        return $user->can('force-delete-tenant') && 
               $user->can('force-delete-any-tenant');
    }
}
