<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use \App\Models\Traits\HasTenantScope;

class Address extends Model
{
    use HasFactory, HasTenantScope;

    protected $primaryKey = 'address_id';

    protected $fillable = [
        'tenant_id',
        'addressable_id',
        'addressable_type',
        'address_type',
        'street_address_line_1',
        'street_address_line_2',
        'city',
        'state_province',
        'postal_code',
        'country_code',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    /**
     * Get the parent addressable model (Customer, CrmUser, etc.).
     */
    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }
}