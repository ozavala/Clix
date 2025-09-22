<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use \App\Models\Traits\HasTenantScope;

class TaxCollection extends Model
{
    use HasFactory, SoftDeletes, HasTenantScope;

    protected $primaryKey = 'tax_collection_id';

    protected $fillable = [
        'tenant_id',
        'invoice_id',
        'quotation_id',
        'tax_rate_id',
        'taxable_amount',
        'tax_amount',
        'collection_type',
        'collection_date',
        'customer_name',
        'description',
        'status',
        'remittance_date',
        'created_by_user_id',
    ];

    protected $casts = [
        'taxable_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'collection_date' => 'date',
        'remittance_date' => 'date',
    ];

    public static $collectionTypes = [
        'sale' => 'Venta',
        'service' => 'Servicio',
    ];

    public static $statuses = [
        'collected' => 'Cobrado',
        'pending' => 'Pendiente',
        'refunded' => 'Reembolsado',
    ];

    /**
     * Get the invoice associated with this tax collection.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'invoice_id');
    }

    /**
     * Get the quotation associated with this tax collection.
     */
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class, 'quotation_id', 'quotation_id');
    }

    /**
     * Get the tax rate associated with this collection.
     */
    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class, 'tax_rate_id', 'tax_rate_id');
    }

    /**
     * Get the user who created this tax collection.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'created_by_user_id', 'user_id');
    }

    /**
     * Check if the tax collection has been remitted.
     */
    public function isRemitted(): bool
    {
        return $this->status === 'remitted';
    }

    /**
     * Mark the tax collection as remitted.
     */
    public function markAsRemitted(): void
    {
        $this->status = 'remitted';
        $this->remittance_date = now();
        $this->save();
    }

    /**
     * Get the remittance period in days.
     */
    public function getRemittancePeriodAttribute(): ?int
    {
        if ($this->remittance_date && $this->collection_date) {
            return $this->collection_date->diffInDays($this->remittance_date);
        }
        return null;
    }
}
