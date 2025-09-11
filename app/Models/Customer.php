<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Traits\HasTenantScope;

class Customer extends Model
{
    use HasFactory, SoftDeletes, HasTenantScope;

    protected $primaryKey = 'customer_id';

    protected $fillable = [
        'tenant_id',
        'type',
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'company_name',
        'legal_id',
        'address_street',
        'address_city',
        'address_state',
        'address_postal_code',
        'address_country',
        'status', // Example: Active, Inactive, Lead 
        // 'notes', // Removed to avoid conflict with polymorphic relationship
        'created_by_user_id',
    ];

    /**
     * Get the full name of the customer.
     */
    public function getFullNameAttribute(): string
    {
        $full_name ="{$this->first_name} {$this->last_name}";
        return $full_name;
    }


    /**
     * Get the user who created this customer.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'created_by_user_id', 'user_id');
    }

     public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable');
    }
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'customer_id', 'customer_id');
    }
    /**
     * Get all of the customer's orders.
     */
    public function orders()
    {
        return $this->hasMany(\App\Models\Order::class, 'customer_id', 'customer_id');
    }
    /**
     * Get all of the customer's contacts.
     */
    public function contacts(): MorphMany
    {
        return $this->morphMany(Contact::class, 'contactable');
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable');
    }

    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'taskable');
    }

    public function invoices()
    {
        return $this->hasMany(\App\Models\Invoice::class, 'customer_id', 'customer_id');
    }

    public function getAllPayments()
    {
        // Get payments from invoices
        $invoicePayments = Payment::whereHas('payable', function ($query) {
            $query->where('payable_type', Invoice::class)
                  ->whereIn('payable_id', $this->invoices->pluck('invoice_id'));
        })->get();

        // Get payments from orders
        $orderPayments = Payment::whereHas('payable', function ($query) {
            $query->where('payable_type', Order::class)
                  ->whereIn('payable_id', $this->orders->pluck('order_id'));
        })->get();

        return $invoicePayments->merge($orderPayments)->sortByDesc('payment_date');
    }
}
