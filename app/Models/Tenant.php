<?php

namespace App\Models;

use App\Services\ConfigurationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\App;


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
        'logo',
        'email',
        'slogan',
        
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'subscription_ends_at' => 'datetime',
        'settings' => 'array',
    ];
    
    /**
     * The "booting" method of the model.
     */
    protected static function booted()
    {
        static::created(function ($tenant) {
            $tenant->initializeConfiguration();
        });
        
        static::updated(function ($tenant) {
            $tenant->syncSettingsWithTenant();
        });
    }

    /**
     * Get the users that belong to the tenant.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'crm_user_tenat')
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
     * Initialize configuration for the tenant
     */
    public function initializeConfiguration()
    {
        $configService = app(ConfigurationService::class);
        $configService->setTenantId($this->id);
        $configService->initializeCoreSettings($this->id);
        
        // Map tenant attributes to settings
        $settings = [
            'company' => [
                'name' => $this->name,
                'legal_id' => $this->legal_id,
                'address' => $this->address,
                'phone' => $this->phone,
                'email' => $this->email,
                'website' => $this->website,
                'logo' => $this->logo,
                'slogan' => $this->slogan,
            ]
        ];
        
        foreach ($settings as $group => $values) {
            $configService->setMany($values, $group);
        }
    }
    
    /**
     * Sync settings with tenant attributes
     */
    public function syncSettingsWithTenant()
    {
        $configService = app(ConfigurationService::class);
        $configService->setTenantId($this->id);
        $companySettings = $configService->getByGroup('company');
        
        $this->update([
            'name' => $companySettings['name'] ?? $this->name,
            'legal_id' => $companySettings['legal_id'] ?? $this->legal_id,
            'address' => $companySettings['address'] ?? $this->address,
            'phone' => $companySettings['phone'] ?? $this->phone,
            'email' => $companySettings['email'] ?? $this->email,
            'website' => $companySettings['website'] ?? $this->website,
            'logo' => $companySettings['logo'] ?? $this->logo,
            'slogan' => $companySettings['slogan'] ?? $this->slogan,
        ]);
    }
    
    /**
     * Get the configuration service instance
     */
    public function config()
    {
        $configService = app(ConfigurationService::class);
        $configService->setTenantId($this->id);
        return $configService;
    }
    
    /**
     * Get a configuration value
     */
    public function getConfig($key, $default = null, $group = 'general')
    {
        return $this->config()->get($key, $default, $group);
    }
    
    /**
     * Set a configuration value
     */
    public function setConfig($key, $value, $group = 'general')
    {
        return $this->config()->set($key, $value, $group);
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