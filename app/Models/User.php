<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'tenant_id',
        'is_super_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
            'tenant_id' => 'integer',
        ];
    }

    /**
     * Get the tenant that the user belongs to.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * The tenants that the user has access to.
     */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_user')
            ->withTimestamps()
            ->withPivot('is_owner');
    }

    /**
     * Check if the user is a super admin.
     */
    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    /**
     * Check if the user is an owner of the given tenant.
     */
    public function isTenantOwner(Tenant $tenant): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->tenants()
            ->where('tenant_id', $tenant->id)
            ->wherePivot('is_owner', true)
            ->exists();
    }

    /**
     * Check if the user has access to the given tenant.
     */
    public function hasTenantAccess(Tenant $tenant): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->tenants()->where('tenant_id', $tenant->id)->exists() || 
               $this->tenant_id === $tenant->id;
    }

    /**
     * Get the current tenant for the user.
     */
    public function getCurrentTenantAttribute(): ?Tenant
    {
        if (session()->has('current_tenant_id')) {
            return $this->tenants()->find(session('current_tenant_id')) ?? $this->tenant;
        }

        return $this->tenant;
    }
}
