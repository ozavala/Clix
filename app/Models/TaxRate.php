<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxRate extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'tax_rate_id';

    protected $fillable = [
        'tenant_id',
        'name',
        'rate',
        'country_code',
        'product_type',
        'description',
        'is_active',
        'is_default',
        'created_by_user_id',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * Get the user who created this tax rate.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'created_by_user_id', 'user_id');
    }

    /**
     * Get the products that use this tax rate.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'tax_rate_id', 'tax_rate_id');
    }

    /**
     * Get the default tax rate for a country.
     */
    public static function getDefaultForCountry(string $countryCode = 'ES'): ?self
    {
        return static::where('country_code', $countryCode)
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get active tax rates for a country.
     */
    public static function getActiveForCountry(string $countryCode = 'ES'): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('country_code', $countryCode)
            ->where('is_active', true)
            ->orderBy('rate', 'desc')
            ->get();
    }

    /**
     * Calculate tax amount for a given subtotal.
     */
    public function calculateTaxAmount(float $subtotal): float
    {
        return $subtotal * ($this->rate / 100);
    }

    /**
     * Calculate total with tax for a given subtotal.
     */
    public function calculateTotalWithTax(float $subtotal): float
    {
        return $subtotal + $this->calculateTaxAmount($subtotal);
    }
}
