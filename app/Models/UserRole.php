<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use \App\Models\Traits\HasTenantScope;

class UserRole extends Model
{
    use HasFactory, HasTenantScope;

    protected $primaryKey = 'role_id';

    protected $fillable = [
        'tenant_id',
        'name', // Roles should have a name, e.g., 'Admin', 'Sales'
        'description',
    ];

    // Relationship with CrmUser (many-to-many)
    // Will be defined fully after pivot table migration
    public function users()
    {
        return $this->belongsToMany(CrmUser::class, 'crm_user_user_role', 'role_id', 'crm_user_id')->withTimestamps();
    }

    // Relationship with Permission (many-to-many)
    // Will be defined fully after pivot table migration
    public function permissions()
    {
        $query = $this->belongsToMany(
            Permission::class, 
            'permission_user_role', 
            'role_id', 
            'permission_id'
        )->withPivot('tenant_id');
    
        // Only add the tenant scope if we have a current tenant
        if ($tenant = app('currentTenant')) {
            $query->wherePivot('tenant_id', $tenant->tenant_id);
        }
    
        return $query;
    }
}