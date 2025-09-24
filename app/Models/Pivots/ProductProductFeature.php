<?php

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ProductProductFeature extends Pivot
{
    protected $table = 'product_product_feature';

    protected static function booted()
    {
        static::creating(function ($pivot) {
            if (empty($pivot->tenant_id)) {
                if (app()->bound('currentTenant') && app('currentTenant')) {
                    $pivot->tenant_id = app('currentTenant')->getKey();
                } else {
                    // Try derive from product
                    if (!empty($pivot->product_id)) {
                        $product = \App\Models\Product::find($pivot->product_id);
                        if ($product && $product->tenant_id) {
                            $pivot->tenant_id = $product->tenant_id;
                        }
                    }
                }
            }
        });
    }
}
