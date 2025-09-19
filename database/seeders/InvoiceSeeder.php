<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Str;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed invoices based on orders that are 'Processing' or 'Shipped' etc.
        $ordersToInvoice = Order::whereIn('status', ['Processing', 'Shipped', 'Delivered', 'Completed'])
                                ->with(['customer', 'items', 'createdBy'])
                                ->get();

        if ($ordersToInvoice->isEmpty()) {
            $this->command->info('Skipping InvoiceSeeder: No suitable Orders to invoice.');
            return;
        }

        $i = 0;
        foreach ($ordersToInvoice as $order) {
            if (!$order->customer) continue;

            // Only invoice every second order to leave some for manual creation
            if ($i++ % 2 !== 0) {
                continue;
            }

            // Check if an invoice already exists for this order to avoid duplicates
            if (Invoice::where('order_id', $order->order_id)->exists()) {
                continue;
            }

            $invoiceData = [
                'order_id' => $order->order_id,
                'customer_id' => $order->customer_id,
                'invoice_number' => 'INV-' . strtoupper(Str::random(8)),
                'invoice_date' => $order->order_date->addDays(1), // Invoice date after order date
                'due_date' => $order->order_date->addDays(31),    // Due 30 days after invoice
                'status' => $order->order_id % 2 == 0 ? 'Sent' : 'Draft', // Ensure some viewable statuses
                'subtotal' => $order->subtotal,
                'discount_type' => $order->discount_type,
                'discount_value' => $order->discount_value,
                'discount_amount' => $order->discount_amount,
                'tax_percentage' => $order->tax_percentage,
                'tax_amount' => $order->tax_amount,
                'total_amount' => $order->total_amount,
                'amount_paid' => 0, // Initially unpaid
                'terms_and_conditions' => 'Standard payment terms apply. Please pay within 30 days.',
                'notes' => 'Invoice for Order: ' . $order->order_number,
                'created_by_user_id' => $order->created_by_user_id ?? User::first()->user_id, // Fallback
            ];

            $invoice = Invoice::create($invoiceData);

            // Copy items from order to invoice
            foreach ($order->items as $orderItem) {
                $invoice->items()->create([
                    'product_id' => $orderItem->product_id,
                    'item_name' => $orderItem->item_name,
                    'item_description' => $orderItem->item_description,
                    'quantity' => $orderItem->quantity,
                    'unit_price' => $orderItem->unit_price,
                    'item_total' => $orderItem->item_total,
                ]);
            }

            // Optionally update order status
            // $order->status = 'Completed'; // Or 'Invoiced'
            // $order->save();
        }
    }
}