<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxPayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'tax_payment_id';

    protected $fillable = [
        'tenant_id',
        'purchase_order_id',
        'invoice_id',
        'tax_rate_id',
        'taxable_amount',
        'tax_amount',
        'payment_type',
        'payment_date',
        'document_number',
        'supplier_name',
        'description',
        'status',
        'recovery_date',
        'created_by_user_id',
    ];

    protected $casts = [
        'taxable_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'payment_date' => 'date',
        'recovery_date' => 'date',
    ];

    public static $paymentTypes = [
        'import' => 'Importación',
        'purchase' => 'Compra',
        'service' => 'Servicio',
    ];

    public static $statuses = [
        'paid' => 'Pagado',
        'pending' => 'Pendiente',
        'recovered' => 'Recuperado',
    ];

    /**
     * Get the purchase order associated with this tax payment.
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id', 'purchase_order_id');
    }

    /**
     * Get the invoice associated with this tax payment.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'invoice_id');
    }

    /**
     * Get the tax rate associated with this payment.
     */
    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class, 'tax_rate_id', 'tax_rate_id');
    }

    /**
     * Get the user who created this tax payment.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'created_by_user_id', 'user_id');
    }

    /**
     * Check if the tax payment has been recovered.
     */
    public function isRecovered(): bool
    {
        return $this->status === 'recovered';
    }

    /**
     * Mark the tax payment as recovered.
     */
    public function markAsRecovered(): void
    {
        $this->status = 'recovered';
        $this->recovery_date = now();
        $this->save();
    }

    /**
     * Get the recovery period in days.
     */
    public function getRecoveryPeriodAttribute(): ?int
    {
        if ($this->recovery_date && $this->payment_date) {
            return $this->payment_date->diffInDays($this->recovery_date);
        }
        return null;
    }
}
