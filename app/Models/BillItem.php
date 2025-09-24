<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use \App\Models\Traits\HasTenantScope;

class BillItem extends Model
{
    use HasFactory,SoftDeletes, HasTenantScope;

    protected $primaryKey = 'bill_item_id';

    protected $fillable = [
        'tenant_id',
        'bill_id',
        'purchase_order_item_id',
        'product_id',
        'item_name',
        'item_description',
        'quantity',
        'unit_price',
        'item_total',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class, 'bill_id', 'bill_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class, 'purchase_order_item_id', 'purchase_order_item_id');
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->tenant_id)) {
                if (app()->bound('currentTenant') && app('currentTenant')) {
                    $model->tenant_id = app('currentTenant')->id ?? app('currentTenant')->tenant_id;
                } elseif (!empty($model->bill_id)) {
                    $bill = \App\Models\Bill::find($model->bill_id);
                    if ($bill && $bill->tenant_id) {
                        $model->tenant_id = $bill->tenant_id;
                    }
                } elseif (!empty($model->purchase_order_item_id)) {
                    $poi = \App\Models\PurchaseOrderItem::find($model->purchase_order_item_id);
                    if ($poi && $poi->tenant_id) {
                        $model->tenant_id = $poi->tenant_id;
                    }
                } elseif (!empty($model->product_id)) {
                    $product = \App\Models\Product::find($model->product_id);
                    if ($product && $product->tenant_id) {
                        $model->tenant_id = $product->tenant_id;
                    }
                }
            }
        });
    }
}