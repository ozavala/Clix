<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\Quotation;
use App\Models\Customer;
use App\Models\User;
use App\Models\Address;
use Illuminate\Support\Str;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed orders based on accepted quotations
        $acceptedQuotations = Quotation::where('status', 'Accepted')->with(['opportunity.customer', 'items'])->get();
        $users = User::all();

        if ($acceptedQuotations->isEmpty() || $users->isEmpty()) {
            $this->command->info('Skipping OrderSeeder: Missing Accepted Quotations or Users.');
            return;
        }

        foreach ($acceptedQuotations as $index => $quotation) { // Add $index here
            if (!$quotation->opportunity || !$quotation->opportunity->customer) {
                continue;
            }
            $customer = $quotation->opportunity->customer;
            $user = $users->random();

            // Attempt to find customer addresses
            $shippingAddress = $customer->addresses()->where('address_type', 'Shipping')->orWhere('is_primary', true)->first();
            $billingAddress = $customer->addresses()->where('address_type', 'Billing')->orWhere('is_primary', true)->first();

            // Fallback if specific types not found, use any primary
            if (!$shippingAddress) $shippingAddress = $customer->addresses()->where('is_primary', true)->first();
            if (!$billingAddress) $billingAddress = $customer->addresses()->where('is_primary', true)->first();

            // Fallback to first address if still not found
            if (!$shippingAddress) $shippingAddress = $customer->addresses()->first();
            if (!$billingAddress) $billingAddress = $customer->addresses()->first();


            $orderData = [
                'customer_id' => $customer->customer_id,
                'quotation_id' => $quotation->quotation_id,
                'opportunity_id' => $quotation->opportunity_id,
                'shipping_address_id' => $shippingAddress ? $shippingAddress->address_id : null,
                'billing_address_id' => $billingAddress ? $billingAddress->address_id : null,
                'order_number' => 'ORD-' . strtoupper(Str::random(8)),
                'order_date' => $quotation->expiry_date->addDays(1), // Order placed after quotation acceptance
                'status' => $index % 2 == 0 ? 'Processing' : 'Pending', // Ensure some active statuses
                'subtotal' => $quotation->subtotal,
                'discount_type' => $quotation->discount_type,
                'discount_value' => $quotation->discount_value,
                'discount_amount' => $quotation->discount_amount,
                'tax_percentage' => $quotation->tax_percentage,
                'tax_amount' => $quotation->tax_amount,
                'total_amount' => $quotation->total_amount,
                'notes' => 'Order created from Quotation: ' . $quotation->subject,
                'created_by_user_id' => $user->user_id,
            ];

            $order = Order::create($orderData);

            // Copy items from quotation to order
            foreach ($quotation->items as $quotationItem) {
                $order->items()->create([
                    'product_id' => $quotationItem->product_id,
                    'item_name' => $quotationItem->item_name,
                    'item_description' => $quotationItem->item_description,
                    'quantity' => $quotationItem->quantity,
                    'unit_price' => $quotationItem->unit_price,
                    'item_total' => $quotationItem->item_total,
                ]);
            }

            // Update quotation status if applicable
            if ($order) {
                $quotation->status = 'Invoiced'; // Or 'Ordered' if you have such a status
                $quotation->save();
            }
        }
    }
}