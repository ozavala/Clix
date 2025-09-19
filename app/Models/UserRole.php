<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserRole extends Model
{
    use HasFactory;

    protected $primaryKey = 'role_id';

    protected $fillable = [
        'name', // Roles should have a name, e.g., 'Admin', 'Sales'
        'description',
    ];

    // Relationship with User (many-to-many)
    // Will be defined fully after pivot table migration
    public function users()
    {
        return $this->belongsToMany(User::class, 'crm_user_user_role', 'role_id', 'crm_user_id')->withTimestamps();
    }

    // Relationship with Permission (many-to-many)
    // Will be defined fully after pivot table migration
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'permission_user_role', 'role_id', 'permission_id')->withTimestamps();
    }
}