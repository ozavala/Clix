<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use \App\Models\Traits\HasTenantScope;

class GoodsReceiptItem extends Model
{
    use HasFactory, HasTenantScope;

    protected $primaryKey = 'goods_receipt_item_id';

    protected $fillable = [
        'tenant_id',
        'goods_receipt_id',
        'purchase_order_item_id',
        'product_id',
        'quantity_received',
        'unit_cost_with_landed',
        'total_cost',
        'notes',
    ];

    protected $casts = [
        'quantity_received' => 'integer',
        'unit_cost_with_landed' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->tenant_id)) {
                if (app()->bound('currentTenant') && app('currentTenant')) {
                    $model->tenant_id = app('currentTenant')->id ?? app('currentTenant')->tenant_id;
                } else if (!empty($model->goods_receipt_id)) {
                    $gr = \App\Models\GoodsReceipt::find($model->goods_receipt_id);
                    if ($gr && $gr->tenant_id) {
                        $model->tenant_id = $gr->tenant_id;
                    }
                } else if (!empty($model->purchase_order_item_id)) {
                    $poi = \App\Models\PurchaseOrderItem::find($model->purchase_order_item_id);
                    if ($poi && $poi->tenant_id) {
                        $model->tenant_id = $poi->tenant_id;
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

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class, 'goods_receipt_id', 'goods_receipt_id');
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class, 'purchase_order_item_id', 'purchase_order_item_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }
}

