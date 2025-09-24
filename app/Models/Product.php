<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HasTenantScope;

class Product extends Model
{
    use HasFactory, SoftDeletes, HasTenantScope;

    protected $primaryKey = 'product_id';

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'sku',
        'price',
        'cost',
        'quantity_on_hand',
        'reorder_point',
        'is_service',
        'is_active',
        'created_by_user_id',
        'category_id',
        'tax_rate_id',
        'is_taxable',
        'tax_rate_percentage',
        'tax_category',
        'tax_country_code',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'quantity_on_hand' => 'integer',
        'is_service' => 'boolean',
        'is_active' => 'boolean',
        'is_taxable' => 'boolean',
        'tax_rate_percentage' => 'decimal:2',
    ];

    /**
     * Use product_id for route model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'product_id';
    }

    /**
     * Resolve route binding without tenant scope to avoid 404 when current tenant context differs in tests.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $column = $field ?? $this->getRouteKeyName();
        return static::withoutGlobalScopes()->where($column, $value)->firstOrFail();
    }

    /**
     * Get the user who created this product/service.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'created_by_user_id', 'user_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function getTypeNameAttribute(): string
    {
        return $this->is_service ? 'Service' : 'Product';
    }

    /**
     * The features that belong to the product.
     */
    public function features(): BelongsToMany
    {
        return $this->belongsToMany(ProductFeature::class, 'product_product_feature', 'product_id', 'feature_id')
            ->using(\App\Models\Pivots\ProductProductFeature::class)
            ->withPivot('tenant_id', 'value')
            ->withTimestamps();
    }

    /**
     * The warehouses that stock the product.
     */
    public function warehouses(): BelongsToMany
    {
        return $this->belongsToMany(Warehouse::class, 'product_warehouse', 'product_id', 'warehouse_id')
            ->using(\App\Models\Pivots\ProductWarehouse::class)
            ->withPivot('tenant_id', 'quantity')
            ->withTimestamps();
    }

    /**
     * Get the category that the product belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id', 'category_id');
    }

    /**
     * Get the tax rate that applies to this product.
     */
    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class, 'tax_rate_id', 'tax_rate_id');
    }

    /**
     * Calculate the price with tax included.
     */
    public function getPriceWithTaxAttribute(): float
    {
        if (!$this->is_taxable) {
            return round($this->price, 2);
        }
        if ($this->tax_rate_percentage !== null) {
            return round($this->price * (1 + ($this->tax_rate_percentage / 100)), 2);
        }
        if ($this->taxRate) {
            return round($this->taxRate->calculateTotalWithTax($this->price), 2);
        }
        return round($this->price, 2);
    }

    /**
     * Calculate the tax amount for this product.
     */
    public function getTaxAmountAttribute(): float
    {
        if (!$this->is_taxable) {
            return 0.0;
        }
        // Prioridad: tasa específica > modelo > 0
        if ($this->tax_rate_percentage !== null) {
            return round($this->price * ($this->tax_rate_percentage / 100), 2);
        }
        if ($this->taxRate) {
            return round($this->taxRate->calculateTaxAmount($this->price), 2);
        }
        return 0.0;
    }

    /**
     * Get the effective tax rate for this product.
     */
    public function getEffectiveTaxRateAttribute(): float
    {
        if (!$this->is_taxable) {
            return 0.0;
        }
        if ($this->tax_rate_percentage !== null) {
            return round($this->tax_rate_percentage, 2);
        }
        if ($this->taxRate) {
            return round($this->taxRate->rate, 2);
        }
        return 0.0;
    }

    /**
     * Get tax rates available for this product's country.
     */
    public function getAvailableTaxRates(): array
    {
        $countryCode = $this->tax_country_code ?? 'EC';
        $settingKey = "tax_rates_{$countryCode}";
        
        $setting = \App\Models\Setting::where('key', $settingKey)->first();
        if ($setting) {
            return json_decode($setting->value, true);
        }
        
        // Fallback a tasas por defecto
        return [
            ['name' => 'IVA 0%', 'rate' => 0.00, 'description' => 'Productos exentos de IVA'],
            ['name' => 'IVA 15%', 'rate' => 15.00, 'description' => 'Tasa general de IVA'],
            ['name' => 'IVA 22%', 'rate' => 22.00, 'description' => 'Tasa especial de IVA'],
        ];
    }

    /**
     * Check if this product category is taxable.
     */
    public function isCategoryTaxable(): bool
    {
        $taxableCategories = ['goods', 'services'];
        $nonTaxableCategories = ['transport_public'];
        
        if (in_array($this->tax_category, $nonTaxableCategories)) {
            return false;
        }
        
        return in_array($this->tax_category, $taxableCategories) || $this->tax_category === null;
    }

    /**
     * Updates inventory quantity and recalculates the weighted average cost.
     *
     * @param int $quantityReceived The number of units being added to stock.
     * @param float $landedCostPerUnit The full landed cost for each of those units.
     */
    public function receiveStock(int $quantityReceived, float $landedCostPerUnit): void
    {
        $oldQuantity = $this->quantity_on_hand;
        $oldAverageCost = $this->cost;

        $newStockValue = $quantityReceived * $landedCostPerUnit;
        $oldStockValue = $oldQuantity * $oldAverageCost;

        $totalQuantity = $oldQuantity + $quantityReceived;
        $totalValue = $oldStockValue + $newStockValue;

        $this->quantity_on_hand = $totalQuantity;
        $this->cost = ($totalQuantity > 0) ? $totalValue / $totalQuantity : 0;

        $this->save();
    }
}