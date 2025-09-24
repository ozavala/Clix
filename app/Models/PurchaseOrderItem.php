<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use \App\Models\Traits\HasTenantScope;

class PurchaseOrderItem extends Model
{
    use HasFactory, HasTenantScope;

    protected $primaryKey = 'purchase_order_item_id';

    protected $fillable = [
        'tenant_id',
        'purchase_order_id',
        'product_id',
        'item_name',
        'item_description',
        'quantity',
        'unit_price',
        'item_total',
        'landed_cost_per_unit',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'item_total' => 'decimal:2',
        'landed_cost_per_unit' => 'decimal:4',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->tenant_id)) {
                if (app()->bound('currentTenant') && app('currentTenant')) {
                    $model->tenant_id = app('currentTenant')->id ?? app('currentTenant')->tenant_id;
                } else if (!empty($model->purchase_order_id)) {
                    $po = \App\Models\PurchaseOrder::find($model->purchase_order_id);
                    if ($po && $po->tenant_id) {
                        $model->tenant_id = $po->tenant_id;
                    }
                } else if (!empty($model->product_id)) {
                    $product = \App\Models\Product::find($model->product_id);
                    if ($product && $product->tenant_id) {
                        $model->tenant_id = $product->tenant_id;
                    }
                }
            }
        });
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id', 'purchase_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }
}