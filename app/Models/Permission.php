<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use \App\Models\Traits\HasTenantScope;

class Permission extends Model
{
    use HasFactory, HasTenantScope;

    protected $primaryKey = 'permission_id';

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
    ];

    // Relationship with UserRole (many-to-many)
    // Will be defined fully after pivot table migration
    public function roles()
    {
        return $this->belongsToMany(UserRole::class, 'permission_user_role', 'permission_id', 'role_id')
               ->withPivot('tenant_id')
               ->wherePivot('tenant_id', app('currentTenant')->id);
    }
}