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

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->tenant_id)) {
                if (app()->bound('currentTenant') && app('currentTenant')) {
                    $model->tenant_id = app('currentTenant')->tenant_id ?? app('currentTenant')->id;
                }
            }
        });
    }

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
        return $this->belongsToMany(
            Permission::class, 
            'permission_user_role', 
            'role_id', 
            'permission_id'
        )->withPivot('tenant_id');
    }
}