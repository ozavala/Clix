<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HasTenantScope;

class Bill extends Model
{
    use HasFactory, SoftDeletes, HasTenantScope;

    protected $primaryKey = 'bill_id';

    protected $fillable = [
        'tenant_id',
        'purchase_order_id',
        'supplier_id',
        'bill_number',
        'bill_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'total_amount',
        'amount_paid',
        'status',
        'notes',
        'created_by_user_id',
    ];

    protected $casts = [
        'bill_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

    public static $statuses = [
        'Draft' => 'Draft',
        'Awaiting Payment' => 'Awaiting Payment',
        'Partially Paid' => 'Partially Paid',
        'Paid' => 'Paid',
        'Cancelled' => 'Cancelled',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id', 'purchase_order_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BillItem::class, 'bill_id', 'bill_id');
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function getAmountDueAttribute()
    {
        return $this->total_amount - $this->amount_paid;
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
            $this->status = 'Awaiting Payment';
        }
        
        $this->save();
    }
}