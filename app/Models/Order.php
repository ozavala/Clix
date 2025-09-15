<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\Traits\HasTenantScope;

class Order extends Model
{
    use HasFactory, SoftDeletes, HasTenantScope;

    protected $primaryKey = 'order_id';

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'quotation_id',
        'opportunity_id',
        'shipping_address_id',
        'billing_address_id',
        'order_number',
        'order_date',
        'status',
        'subtotal',
        'discount_type',
        'discount_value',
        'discount_amount',
        'tax_percentage',
        'tax_amount',
        'total_amount',
        'amount_paid',
        'notes',
        'created_by_user_id',
    ];

    protected $casts = [
        'order_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

    public static $statuses = [
        'Pending' => 'Pending',
        'Processing' => 'Processing',
        'Shipped' => 'Shipped',
        'Delivered' => 'Delivered',
        'Completed' => 'Completed',
        'Cancelled' => 'Cancelled',
        'Partially Paid' => 'Partially Paid',
        'Paid' => 'Paid',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class, 'quotation_id', 'quotation_id');
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class, 'opportunity_id', 'opportunity_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'created_by_user_id', 'user_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'order_id');
    }

    // If using the central 'addresses' table with polymorphic relations:
    // public function shippingAddress() //: MorphTo - if addressable_type is stored on addresses table
    // {
    //     return $this->belongsTo(Address::class, 'shipping_address_id', 'address_id');
    // }
    // public function billingAddress()
    // {
    //     return $this->belongsTo(Address::class, 'billing_address_id', 'address_id');
    // }

    /**
     * Get the invoices for the order.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'order_id', 'order_id');
    }

    /**
     * Get all of the order's payments.
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    /**
     * Get the amount due for the order.
     */
    public function getAmountDueAttribute()
    {
        return $this->total_amount - $this->amount_paid;
    }

    /**
     * Updates the status of the order based on its payments.
     */
    public function updateStatusAfterPayment(): void
    {
        $this->amount_paid = $this->payments()->sum('amount');

        if ($this->amount_paid >= $this->total_amount) {
            $this->status = 'Paid';
        } elseif ($this->amount_paid > 0) {
            $this->status = 'Partially Paid';
        }
        $this->save();
    }
}