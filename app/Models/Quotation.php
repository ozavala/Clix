<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quotation extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'quotation_id';

    protected $fillable = [
        'tenant_id',
        'opportunity_id',
        'subject',
        'quotation_date',
        'expiry_date',
        'status',
        'subtotal',
        'discount_type',
        'discount_value',
        'discount_amount',
        'tax_percentage',
        'tax_amount',
        'total_amount',
        'terms_and_conditions',
        'notes',
        'created_by_user_id',
    ];

    protected $casts = [
        'quotation_date' => 'date',
        'expiry_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public static $statuses = [
        'Draft' => 'Draft',
        'Sent' => 'Sent',
        'Accepted' => 'Accepted',
        'Declined' => 'Declined',
        'Invoiced' => 'Invoiced',
        // Add more as needed
    ];

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

    /**
     * Get the items for the quotation.
     */
    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class, 'quotation_id', 'quotation_id');
    }
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class, 'quotation_id', 'quotation_id');
    }

    /**
     * Get the order associated with the quotation.
     */
    public function order(): HasOne // A quotation typically converts to one order
    {
        return $this->hasOne(Order::class, 'quotation_id', 'quotation_id');
    }
}