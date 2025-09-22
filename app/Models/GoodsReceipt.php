<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use \App\Models\Traits\HasTenantScope;

class GoodsReceipt extends Model
{
    use HasFactory, HasTenantScope;

    protected $primaryKey = 'goods_receipt_id';

    protected $fillable = [
        'tenant_id',
        'purchase_order_id',
        'received_by_user_id',
        'receipt_date',
        'receipt_number',
        'warehouse_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'receipt_date' => 'date',
    ];

    public static $statuses = [
        'draft' => 'Draft',
        'received' => 'Received',
        'cancelled' => 'Cancelled',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id', 'purchase_order_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'received_by_user_id', 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class, 'goods_receipt_id', 'goods_receipt_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'warehouse_id');
    }

    /**
     * Process the goods receipt and update inventory.
     */
    public function processReceipt(): bool
    {
        if ($this->status !== 'draft') {
            return false;
        }

        \DB::transaction(function () {
            foreach ($this->items as $item) {
                $product = $item->product;
                
                // Update product inventory
                $product->quantity_on_hand += $item->quantity_received;
                
                // Update average cost if landed costs are involved
                if ($item->unit_cost_with_landed) {
                    $product->cost = $item->unit_cost_with_landed;
                }
                
                $product->save();
            }

            // Update purchase order status
            $this->purchaseOrder->updateStatusAfterReceipt();
            
            // Mark receipt as received
            $this->status = 'received';
            $this->save();
        });

        return true;
    }

    /**
     * Get the total value of received items.
     */
    public function getTotalValueAttribute()
    {
        return $this->items->sum(function ($item) {
            return $item->quantity_received * $item->unit_cost_with_landed;
        });
    }
}

