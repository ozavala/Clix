<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ConfigurationService
{
    /**
     * @var int|string|null
     */
    protected $tenantId;
    
    protected $cachePrefix = 'tenant_config_';
    protected $coreGroups = [
        'company' => [
            'name' => [
                'type' => 'string',
                'validation' => 'required|string|max:255',
                'default' => 'My Company',
                'editable' => true,
                'public' => true,
            ],
            'legal_id' => [
                'type' => 'string',
                'validation' => 'required|string|max:50',
                'editable' => true,
                'public' => true,
            ],
            'address' => [
                'type' => 'text',
                'validation' => 'nullable|string',
                'editable' => true,
                'public' => true,
            ],
            'phone' => [
                'type' => 'string',
                'validation' => 'nullable|string|max:20',
                'editable' => true,
                'public' => true,
            ],
            'email' => [
                'type' => 'email',
                'validation' => 'nullable|email|max:255',
                'editable' => true,
                'public' => true,
            ],
            'website' => [
                'type' => 'url',
                'validation' => 'nullable|url|max:255',
                'editable' => true,
                'public' => true,
            ],
            'logo' => [
                'type' => 'image',
                'validation' => 'nullable|string',
                'editable' => true,
                'public' => true,
            ],
            'slogan' => [
                'type' => 'string',
                'validation' => 'nullable|string|max:255',
                'editable' => true,
                'public' => true,
            ],
        ],
        'tax' => [
            'sales_tax_rate' => [
                'type' => 'decimal:2',
                'validation' => 'required|numeric|min:0|max:100',
                'default' => '0.00',
                'editable' => true,
                'public' => false,
            ],
            'tax_id' => [
                'type' => 'string',
                'validation' => 'nullable|string|max:50',
                'editable' => true,
                'public' => false,
            ],
        ],
    ];

    public function __construct($tenantId = null)
    {
        if ($tenantId === null && function_exists('tenant')) {
            $tenantId = tenant('id');
        }
        $this->tenantId = $tenantId;
    }

    /**
     * Set the tenant ID for the configuration service
     * 
     * @param int|string $tenantId
     * @return $this
     */
    public function setTenantId($tenantId)
    {
        $this->tenantId = $tenantId;
        return $this;
    }

    /**
     * Get all settings for the current tenant
     */
    public function all($group = null, $publicOnly = false)
    {
        $cacheKey = $this->getCacheKey('all');
        
        return Cache::remember($cacheKey, now()->addDay(), function () use ($group, $publicOnly) {
            $query = Setting::where('tenant_id', $this->tenantId);
            
            if ($group) {
                $query->where('group', $group);
            }
            
            if ($publicOnly) {
                $query->where('is_public', true);
            }
            
            return $query->get()->groupBy('group')->map(function ($settings) {
                return $settings->pluck('value', 'key');
            })->toArray();
        });
    }

    /**
     * Get a specific setting value
     */
    public function get($key, $default = null, $group = 'general')
    {
        $cacheKey = $this->getCacheKey("$group.$key");
        
        return Cache::remember($cacheKey, now()->addDay(), function () use ($key, $default, $group) {
            $setting = Setting::where([
                'tenant_id' => $this->tenantId,
                'key' => $key,
                'group' => $group,
            ])->first();
            
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set a setting value
     */
    protected function set($key, $value, $group = 'general')
    {
        // Skip if value is null and the field is required
        if ($value === null) {
            $config = $this->getSettingConfig($key, $group);
            if (str_contains(($config['validation'] ?? ''), 'required')) {
                return null;
            }
        }
        
        $this->validate($key, $value, $group);
        
        // Prepare the data to update or create
        $data = [
            'type' => $this->isCoreSetting($key, $group) ? 'core' : 'custom',
            'is_editable' => !$this->isCoreSetting($key, $group) ? 1 : 0,
            'is_public' => $this->isPublicSetting($key, $group) ? 1 : 0,
            'description' => $this->getSettingDescription($key, $group),
            'validation_rules' => $this->getValidationRules($key, $group),
        ];
        
        // Only include the value if it's not null
        if ($value !== null) {
            $data['value'] = $value;
        }
        
        $setting = Setting::updateOrCreate(
            [
                'key' => $key, 
                'tenant_id' => $this->tenantId, 
                'group' => $group
            ],
            $data
        );
        
        $this->clearCache($key, $group);
        
        return $setting;
    }

    /**
     * Set multiple settings at once
     */
    public function setMany(array $settings, $group = 'general')
    {
        DB::beginTransaction();
        
        try {
            foreach ($settings as $key => $value) {
                $this->set($key, $value, $group);
            }
            
            DB::commit();
            $this->clearCache();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Initialize core settings for a tenant
     * 
     * @param int|string|null $tenantId
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function initializeCoreSettings($tenantId = null)
    {
        if ($tenantId !== null) {
            $this->tenantId = $tenantId;
        }
        
        if (!$this->tenantId) {
            throw new \InvalidArgumentException('Tenant ID is required to initialize settings');
        }
        
        DB::beginTransaction();
        
        try {
            foreach ($this->coreGroups as $group => $settings) {
                foreach ($settings as $key => $config) {
                    // Skip if no default value is provided and the field is required
                    if (!array_key_exists('default', $config) && 
                        str_contains(($config['validation'] ?? ''), 'required')) {
                        continue;
                    }
                    
                    $this->set(
                        $key,
                        $config['default'] ?? null,
                        $group
                    );
                }
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
        
        $this->clearCache();
        return true;
    }

    /**
     * Validate setting value against its rules
     */
    public function validate($key, $value, $group = 'general')
    {
        $setting = $this->getSettingDefinition($key, $group);
        
        if (!$setting) {
            return true; // Custom settings with no specific validation
        }
        
        $validator = Validator::make(
            [$key => $value],
            [$key => $setting['validation'] ?? 'nullable']
        );
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        
        return true;
    }

    /**
     * Check if a setting is a core setting
     */
    protected function isCoreSetting($key, $group)
    {
        return isset($this->coreGroups[$group][$key]);
    }

    /**
     * Get setting definition
     */
    protected function getSettingDefinition($key, $group)
    {
        return $this->coreGroups[$group][$key] ?? null;
    }

    /**
     * Get setting config
     */
    protected function getSettingConfig($key, $group = 'general')
    {
        return $this->coreGroups[$group][$key] ?? null;
    }
    
    protected function getSettingDescription($key, $group = 'general')
    {
        $config = $this->getSettingConfig($key, $group);
        return $config['description'] ?? ucfirst(str_replace('_', ' ', $key));
    }
    
    protected function getValidationRules($key, $group = 'general')
    {
        $config = $this->getSettingConfig($key, $group);
        return $config['validation'] ?? null;
    }
    
    /**
     * Check if a setting is public
     */
    protected function isPublicSetting($key, $group = 'general')
    {
        $config = $this->getSettingConfig($key, $group);
        return (bool)($config['public'] ?? false);
    }

    /**
     * Clear configuration cache
     */
    public function clearCache($key = null, $group = 'general')
    {
        if ($key) {
            Cache::forget($this->getCacheKey("$group.$key"));
        } else {
            // Clear all cached settings for this tenant
            $keys = Cache::get($this->getCacheKey('keys'), []);
            foreach ($keys as $cacheKey) {
                Cache::forget($cacheKey);
            }
            Cache::forget($this->getCacheKey('keys'));
        }
    }

    /**
     * Get cache key for a setting
     */
    protected function getCacheKey($key)
    {
        $cacheKey = $this->cachePrefix . $this->tenantId . '_' . $key;
        
        // Track all cache keys for this tenant
        $keys = Cache::get($this->cachePrefix . $this->tenantId . '_keys', []);
        if (!in_array($cacheKey, $keys)) {
            $keys[] = $cacheKey;
            Cache::forever($this->cachePrefix . $this->tenantId . '_keys', $keys);
        }
        
        return $cacheKey;
    }

    /**
     * Get all core setting definitions
     */
    public function getCoreDefinitions()
    {
        return $this->coreGroups;
    }

    /**
     * Get public settings for API
     */
    public function getPublicSettings()
    {
        return $this->all(null, true);
    }

    /**
     * Get settings by group
     */
    public function getByGroup($group, $publicOnly = false)
    {
        $settings = $this->all($group, $publicOnly);
        return $settings[$group] ?? [];
    }

    /**
     * Delete a setting
     */
    public function delete($key, $group = 'general')
    {
        if ($this->isCoreSetting($key, $group)) {
            throw new \RuntimeException("Cannot delete core setting: $group.$key");
        }
        
        $deleted = Setting::where([
            'tenant_id' => $this->tenantId,
            'key' => $key,
            'group' => $group,
        ])->delete();
        
        if ($deleted) {
            $this->clearCache($key, $group);
        }
        
        return $deleted > 0;
    }
}
