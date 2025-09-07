<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'legal_id',
        'address',
        'phone',
        'website',
        'industry',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'tenant_id');
    }
}