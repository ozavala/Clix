<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'invoice_id';

    protected $fillable = [
        'tenant_id',
        'order_id',
        'quotation_id',
        'customer_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'status',
        'subtotal',
        'discount_type',
        'discount_value',
        'discount_amount',
        'tax_percentage',
        'tax_amount',
        'total_amount',
        'amount_paid',
        'terms_and_conditions',
        'notes',
        'created_by_user_id',
        'tax_rate_id',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

    public static $statuses = [
        'Draft' => 'Draft',
        'Sent' => 'Sent',
        'Paid' => 'Paid',
        'Partially Paid' => 'Partially Paid',
        'Overdue' => 'Overdue',
        'Void' => 'Void',
        'Cancelled' => 'Cancelled',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'created_by_user_id', 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id', 'invoice_id');
    }

    /**
     * Get all of the invoice's payments.
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function getAmountDueAttribute()
    {
        return $this->total_amount - $this->amount_paid;
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class, 'quotation_id', 'quotation_id');
    }

    /**
     * Get the tax rate that applies to this invoice.
     */
    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class, 'tax_rate_id', 'tax_rate_id');
    }

    /**
     * Calculate tax amount based on subtotal and tax rate.
     */
    public function calculateTaxAmount(): float
    {
        if ($this->taxRate) {
            return $this->taxRate->calculateTaxAmount($this->subtotal);
        }
        return $this->tax_amount;
    }

    /**
     * Calculate total with tax.
     */
    public function calculateTotalWithTax(): float
    {
        return $this->subtotal + $this->calculateTaxAmount();
    }

    public function updateStatusAfterPayment(): void
    {
        $totalPaid = $this->payments()->sum('amount');
        $this->amount_paid = $totalPaid;

        if (bccomp($totalPaid, $this->total_amount, 2) >= 0) {
            $this->status = 'Paid';
        } elseif ($totalPaid > 0) {
            $this->status = 'Partially Paid';
        } else {
            $this->status = 'Sent';
        }

        $this->save();
    }
}