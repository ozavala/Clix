<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HasTenantScope;

class Opportunity extends Model
{
    use HasFactory, SoftDeletes, HasTenantScope;

    protected $primaryKey = 'opportunity_id';

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'lead_id',
        'contact_id', 
        'customer_id',
        'stage',
        'amount',
        'expected_close_date',
        'probability',
        'assigned_to_user_id',
        'created_by_user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expected_close_date' => 'date',
        'probability' => 'integer',
    ];

    public static $stages = [
        'Qualification' => 'Qualification',
        'Needs Analysis' => 'Needs Analysis',
        'Proposal' => 'Proposal',
        'Negotiation' => 'Negotiation',
        'Closed Won' => 'Closed Won',
        'Closed Lost' => 'Closed Lost',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id', 'lead_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id', 'contact_id');
    }


    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'assigned_to_user_id', 'user_id');
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
     * Get the quotations for the opportunity.
     */
    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class, 'opportunity_id', 'opportunity_id');
    }

    /**
     * Get the orders for the opportunity.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'opportunity_id', 'opportunity_id');
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable');
    }

    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'taskable');
    }
}