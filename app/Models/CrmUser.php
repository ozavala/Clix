<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Notifiable;
use App\Models\UserRole; // Ensure you have the correct namespace for UserRole
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use \App\Models\Traits\HasTenantScope;

 
class CrmUser extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasTenantScope;

    protected $primaryKey = 'user_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'username',
        'full_name',
        'email',
        'password',
        'locale',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
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
        ];
    }

    /**
     * Determine if the user has verified their email address.
     * En desarrollo, siempre retorna true para evitar problemas de verificación.
     */
    public function hasVerifiedEmail(): bool
    {
        if (app()->environment('local', 'development')) {
            return true;
        }
        
        return ! is_null($this->email_verified_at);
    }

    /**
     * Mark the given user's email as verified.
     */
    public function markEmailAsVerified(): bool
    {
        if (app()->environment('local', 'development')) {
            return true;
        }
        
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    /**
     * Send the email verification notification.
     */
    public function sendEmailVerificationNotification(): void
    {
        if (app()->environment('local', 'development')) {
            return; // No enviar emails en desarrollo
        }
        
        parent::sendEmailVerificationNotification();
    }
    /**
     * The user that the CRM user belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The roles that belong to the CRM user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(UserRole::class, 'crm_user_user_role', 'crm_user_id', 'role_id')->withTimestamps();
    }
    public function hasPermissionTo(string $permissionName): bool
    {
        return $this->roles()->whereHas('permissions', function ($query) use ($permissionName) {
            $query->where('name', $permissionName);
        })->exists();
    }

    /**
     * Accessor for the 'name' attribute, for compatibility with Breeze.
     */
    public function getNameAttribute(): string
    {
        return $this->full_name;
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * The tenants that belong to the user.
     */
    /**
     * The tenants that belong to the user.
     */
    /**
     * The tenants that belong to the user.
     */
    public function tenants()
    {
        return $this->belongsToMany(Tenant::class, 'crm_user_tenant', 'crm_user_id', 'tenant_id')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    /**
     * Get the user's primary tenant.
     */
    public function primaryTenant()
    {
        return $this->tenants()->wherePivot('is_primary', true)->first();
    }

    /**
     * Check if the user is a super admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->roles()->where('name', 'superadmin')->exists();
    }

    /**
     * Check if the user has access to the given tenant.
     */
    public function hasTenantAccess(Tenant $tenant): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        return $this->tenants()->where('tenant_id', $tenant->id)->exists();
    }

    /**
     * Set the user's primary tenant.
     */
    public function setPrimaryTenant(Tenant $tenant): void
    {
        if (!$this->hasTenantAccess($tenant)) {
            throw new \RuntimeException('User does not have access to this tenant');
        }

        // Reset primary status for all other tenants
        \DB::table('crm_user_tenant')
            ->where('crm_user_id', $this->user_id)
            ->update(['is_primary' => false]);

        // Set the new primary tenant
        $this->tenants()->updateExistingPivot($tenant->id, ['is_primary' => true]);
    }

    public function leads() 
    {
        return $this->hasMany(Lead::class, 'created_by_user_id', 'user_id');
    }
    public function customers() 
    {
        return $this->hasMany(Customer::class, 'created_by_user_id', 'user_id');
    }
    public function addresses()
    {
        return $this->morphMany(Address::class, 'addressable');
    }
}
   
