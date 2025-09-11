<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'legal_id',
        'address',
        'phone',
        'website',
        'industry',
        'is_active',
        'subscription_plan',
        'subscription_ends_at',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'subscription_ends_at' => 'datetime',
        'settings' => 'array',
    ];

    /**
     * Get the users that belong to the tenant.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_user')
            ->withPivot('is_owner')
            ->withTimestamps();
    }

    /**
     * Get the owners of the tenant.
     */
    public function owners(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_user')
            ->wherePivot('is_owner', true)
            ->withTimestamps();
    }

    /**
     * Get all of the transactions for the tenant.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'tenant_id');
    }

    /**
     * Check if the tenant has an active subscription.
     */
    public function hasActiveSubscription(): bool
    {
        if ($this->subscription_ends_at === null) {
            return true; // No subscription end date means it's a free plan or unlimited
        }

        return $this->subscription_ends_at->isFuture();
    }

    /**
     * Scope a query to only include active tenants.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get a setting value.
     */
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Set a setting value.
     */
    public function setSetting(string $key, $value): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->settings = $settings;
    }

    /**
     * Check if the tenant has a specific feature enabled.
     */
    public function hasFeature(string $feature): bool
    {
        $features = $this->getSetting('features', []);
        return in_array($feature, $features, true);
    }

    /**
     * Get the tenant's logo URL.
     */
    public function getLogoUrlAttribute(): ?string
    {
        $logoPath = $this->getSetting('logo_path');
        return $logoPath ? asset("storage/tenants/{$this->id}/" . $logoPath) : null;
    }
}