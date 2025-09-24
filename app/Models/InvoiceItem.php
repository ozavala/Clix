<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use \App\Models\Traits\HasTenantScope;

class InvoiceItem extends Model
{
    use HasFactory, HasTenantScope;

    protected $primaryKey = 'invoice_item_id';

    protected $fillable = [
        'tenant_id',
        'invoice_id',
        'product_id',
        'item_name',
        'item_description',
        'quantity',
        'unit_price',
        'item_total',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->tenant_id)) {
                if (app()->bound('currentTenant') && app('currentTenant')) {
                    $model->tenant_id = app('currentTenant')->id ?? app('currentTenant')->tenant_id;
                } else if (!empty($model->invoice_id)) {
                    $invoice = \App\Models\Invoice::find($model->invoice_id);
                    if ($invoice && $invoice->tenant_id) {
                        $model->tenant_id = $invoice->tenant_id;
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

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'item_total' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'invoice_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }
}