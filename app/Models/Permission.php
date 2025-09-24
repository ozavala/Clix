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

    protected static function booted()
    {
        static::creating(function (Permission $permission) {
            if (empty($permission->tenant_id) && app()->bound('currentTenant')) {
                $tenant = app('currentTenant');
                if ($tenant) {
                    $permission->tenant_id = $tenant->getKey();
                }
            }
        });
    }

    // Relationship with UserRole (many-to-many)
    // Will be defined fully after pivot table migration
    public function roles()
    {
        $query = $this->belongsToMany(UserRole::class, 'permission_user_role', 'permission_id', 'role_id')
               ->withPivot('tenant_id');
        if (app()->bound('currentTenant') && app('currentTenant')) {
            $query->wherePivot('tenant_id', app('currentTenant')->id);
        }
        return $query;
    }
}