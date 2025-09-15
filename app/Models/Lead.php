<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Traits\HasTenantScope;

class Lead extends Model
{
    use HasFactory, SoftDeletes, HasTenantScope;

    protected $primaryKey = 'lead_id';

    protected $fillable = [
        'tenant_id',
        'title',
        'description',
        'value',
        'status',
        'source',
        'customer_id',
        'contact_name',
        'contact_email',
        'contact_phone',
        'assigned_to_user_id',
        'created_by_user_id',
        'expected_close_date',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'expected_close_date' => 'date',
    ];

    /**
     * Get the customer associated with the lead.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    /**
     * Get the user to whom the lead is assigned.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'assigned_to_user_id', 'user_id');
    }

    /**
     * Get the user who created the lead.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'created_by_user_id', 'user_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Get all activities associated with the lead.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'lead_id', 'lead_id')->latest('activity_date');
    }
     /**
     * Get the opportunity associated with the lead, if converted.
     */
    public function opportunity(): HasOne
    {
        return $this->hasOne(Opportunity::class, 'lead_id', 'lead_id');
    }


    // You can add accessors/mutators or other methods as needed
    // For example, a method to get a formatted value:
    // public function getFormattedValueAttribute() { ... }
}