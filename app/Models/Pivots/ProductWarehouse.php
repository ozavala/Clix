<?php

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ProductWarehouse extends Pivot
{
    protected $table = 'product_warehouse';

    protected static function booted()
    {
        static::creating(function ($pivot) {
            if (empty($pivot->tenant_id)) {
                if (app()->bound('currentTenant') && app('currentTenant')) {
                    $pivot->tenant_id = app('currentTenant')->id ?? app('currentTenant')->tenant_id;
                } else {
                    // Derive from product or warehouse if available
                    if (!empty($pivot->product_id)) {
                        $product = \App\Models\Product::find($pivot->product_id);
                        if ($product && $product->tenant_id) {
                            $pivot->tenant_id = $product->tenant_id;
                        }
                    }
                    if (empty($pivot->tenant_id) && !empty($pivot->warehouse_id)) {
                        $warehouse = \App\Models\Warehouse::find($pivot->warehouse_id);
                        if ($warehouse && $warehouse->tenant_id) {
                            $pivot->tenant_id = $warehouse->tenant_id;
                        }
                    }
                }
            }
        });
    }
}
