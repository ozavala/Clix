<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use \App\Models\Traits\HasTenantScope;

class OrderItem extends Model
{
    use HasFactory, hasTenantScope;

    protected $primaryKey = 'order_item_id';

    protected $fillable = [
        'tenant_id',
        'order_id',
        'product_id',
        'item_name',
        'item_description',
        'quantity',
        'unit_price',
        'item_total',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'item_total' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->tenant_id)) {
                if (app()->bound('currentTenant') && app('currentTenant')) {
                    $model->tenant_id = app('currentTenant')->id ?? app('currentTenant')->tenant_id;
                } elseif (!empty($model->order_id)) {
                    $order = \App\Models\Order::find($model->order_id);
                    if ($order && $order->tenant_id) {
                        $model->tenant_id = $order->tenant_id;
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