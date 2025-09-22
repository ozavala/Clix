<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use \App\Models\Traits\HasTenantScope;

class ProductFeature extends Model
{
    use HasFactory, HasTenantScope;

    protected $primaryKey = 'feature_id';

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_product_feature', 'feature_id', 'product_id')->withPivot('value')->withTimestamps();
    }
}