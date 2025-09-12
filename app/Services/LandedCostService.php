<?php

namespace App\Services;

use App\Models\PurchaseOrder;

class LandedCostService
{
    /**
     * Apportions total landed costs across all items in a purchase order
     * and updates the landed_cost_per_unit for each item.
     *
     * @param \App\Models\PurchaseOrder $purchaseOrder
     * @return void
     */
    public function apportionCosts(PurchaseOrder $purchaseOrder): void
    {
        $purchaseOrder->load('items', 'landedCosts');
        \Log::info('Starting apportionCosts', ['purchase_order_id' => $purchaseOrder->purchase_order_id]);

        $totalLandedCosts = $purchaseOrder->landedCosts()->sum('amount');
        $poSubtotal = $purchaseOrder->items()->sum('item_total');

        \Log::info('Initial values', [
            'total_landed_costs' => $totalLandedCosts,
            'po_subtotal' => $poSubtotal,
            'item_count' => $purchaseOrder->items->count()
        ]);

        if ($purchaseOrder->items->isEmpty() || $totalLandedCosts <= 0) {
            \Log::info('No items or no landed costs to apportion');
            foreach ($purchaseOrder->items as $item) {
                $item->landed_cost_per_unit = 0;
                $item->save();
            }
            return;
        }

        $totalLandedCostsStr = (string) $totalLandedCosts;
        $poSubtotalStr = (string) $poSubtotal;

        if (bccomp($poSubtotalStr, '0.00', 2) === 0) {
            \Log::warning('PO subtotal is zero, cannot apportion costs by value.', ['purchase_order_id' => $purchaseOrder->purchase_order_id]);
            foreach ($purchaseOrder->items as $item) {
                $item->landed_cost_per_unit = 0;
                $item->save();
            }
            return;
        }

        foreach ($purchaseOrder->items as $item) {
            if ($item->quantity <= 0) {
                $item->landed_cost_per_unit = 0;
                $item->save();
                continue;
            }

            $itemValue = (string) $item->item_total;
            $itemQuantityStr = (string) $item->quantity;

            $valueProportion = bcdiv($itemValue, $poSubtotalStr, 10);
            $apportionedCost = bcmul($totalLandedCostsStr, $valueProportion, 10);
            $costPerUnit = bcdiv($apportionedCost, $itemQuantityStr, 4);

            \Log::info('Landed Cost Calculation', [
                'item_id' => $item->purchase_order_item_id,
                'item_value' => $itemValue,
                'quantity' => $itemQuantityStr,
                'value_proportion' => $valueProportion,
                'apportioned_cost' => $apportionedCost,
                'cost_per_unit' => $costPerUnit,
            ]);

            $item->landed_cost_per_unit = $costPerUnit;
            $item->save();
        }
    }
}
