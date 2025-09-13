<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Payment;
use App\Models\CrmUser;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class PurchaseOrderStatusFlowSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        // Create a user for the process
        $user = CrmUser::factory()->create();
        $warehouse = Warehouse::factory()->create();

        // Create suppliers
        $suppliers = [
            Supplier::factory()->create(['name' => 'Electronics Supplier A']),
            Supplier::factory()->create(['name' => 'Raw Materials Supplier B']),
            Supplier::factory()->create(['name' => 'Machinery Supplier C']),
        ];

        // Create products
        $products = [
            Product::factory()->create([
                'name' => 'Microprocessor X1',
                'cost' => 500.00,
                'price' => 800.00,
                'created_by_user_id' => $user->user_id,
            ]),
            Product::factory()->create([
                'name' => 'Industrial Sensor Y2',
                'cost' => 200.00,
                'price' => 350.00,
                'created_by_user_id' => $user->user_id,
            ]),
            Product::factory()->create([
                'name' => 'Power Supply Z3',
                'cost' => 150.00,
                'price' => 250.00,
                'created_by_user_id' => $user->user_id,
            ]),
        ];

        $this->command->info('Creating Purchase Order Status Flow Demo...');

        // Demo 1: Complete flow with payments and inventory
        $this->createCompleteFlow($user, $suppliers[0], $products[0], $warehouse, 1);

        // Demo 2: Flow with partial receipt
        $this->createPartialReceiptFlow($user, $suppliers[1], $products[1], $warehouse, 2);

        // Demo 3: Cancelled order
        $this->createCancelledOrder($user, $suppliers[2], $products[2], 3);

        $this->command->info('Purchase Order Status Flow Demo completed!');
    }

    private function createCompleteFlow($user, $supplier, $product, $warehouse, $demoNumber)
    {
        $faker = Faker::create();
        $this->command->info("Demo {$demoNumber}: Complete Flow with Payments and Inventory");

        // 1. Create draft purchase order with unique number
        $uniqueSuffix = strtoupper(Str::random(5));
        $purchaseOrder = PurchaseOrder::factory()->create([
            'supplier_id' => $supplier->supplier_id,
            'purchase_order_number' => "PO-DEMO-{$demoNumber}-001-{$uniqueSuffix}",
            'status' => 'draft',
            'created_by_user_id' => $user->user_id,
        ]);

        $this->command->info("  ✓ Created draft PO: {$purchaseOrder->purchase_order_number}");

        // 2. Add items
        $item = PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $purchaseOrder->purchase_order_id,
            'product_id' => $product->product_id,
            'quantity' => 20,
            'unit_price' => $product->cost,
            'item_total' => 20 * $product->cost,
        ]);

        $purchaseOrder->updateQuietly([
            'subtotal' => $item->item_total,
            'total_amount' => $item->item_total + 100, // Add shipping
        ]);

        // 3. Confirm the order
        $purchaseOrder->confirm();
        $this->command->info("  ✓ Confirmed PO: Status = {$purchaseOrder->status}");

        // 4. Mark as ready for dispatch
        $purchaseOrder->markAsReadyForDispatch();
        $this->command->info("  ✓ Ready for dispatch: Status = {$purchaseOrder->status}");

        // 5. Mark as dispatched
        $purchaseOrder->markAsDispatched();
        $this->command->info("  ✓ Dispatched: Status = {$purchaseOrder->status}");

        // 6. Create partial payment
        Payment::factory()->create([
            'payable_type' => PurchaseOrder::class,
            'payable_id' => $purchaseOrder->purchase_order_id,
            'amount' => $purchaseOrder->total_amount * 0.5,
            'payment_date' => now(),
            'payment_method' => 'bank_transfer',
            'reference_number' => $faker->unique()->numerify("PAY-{$demoNumber}-###"),
        ]);

        $purchaseOrder->updateStatusAfterPayment();
        $this->command->info("  ✓ Partial payment: Status = {$purchaseOrder->status}");

        // 7. Create goods receipt
        $goodsReceipt = GoodsReceipt::factory()->create([
            'purchase_order_id' => $purchaseOrder->purchase_order_id,
            'received_by_user_id' => $user->user_id,
            'warehouse_id' => $warehouse->warehouse_id,
            'receipt_number' => $faker->unique()->numerify("GR-{$demoNumber}-###"),
            'status' => 'draft',
        ]);

        // 8. Add receipt items (full receipt)
        $receiptItem = GoodsReceiptItem::factory()->create([
            'goods_receipt_id' => $goodsReceipt->goods_receipt_id,
            'purchase_order_item_id' => $item->purchase_order_item_id,
            'product_id' => $product->product_id,
            'quantity_received' => 20,
            'unit_cost_with_landed' => $product->cost + 10, // Add landed costs
            'total_cost' => 20 * ($product->cost + 10),
        ]);

        // 9. Process receipt (updates inventory)
        $goodsReceipt->processReceipt();
        $this->command->info("  ✓ Inventory updated: Product quantity = {$product->fresh()->quantity_on_hand}");

        // 10. Complete payment
        Payment::factory()->create([
            'payable_type' => PurchaseOrder::class,
            'payable_id' => $purchaseOrder->purchase_order_id,
            'amount' => $purchaseOrder->total_amount * 0.5,
            'payment_date' => now(),
            'payment_method' => 'bank_transfer',
            'reference_number' => $faker->unique()->numerify("PAY-{$demoNumber}-###"),
        ]);

        $purchaseOrder->updateStatusAfterPayment();
        $this->command->info("  ✓ Full payment: Status = {$purchaseOrder->status}");
        $this->command->info("  ✓ Final PO status: {$purchaseOrder->status}");
        $this->command->info("  ✓ Product cost updated: \${$product->fresh()->cost}");
        $this->command->info("---");
    }

    private function createPartialReceiptFlow($user, $supplier, $product, $warehouse, $demoNumber)
    {
        $faker = Faker::create();
        $this->command->info("Demo {$demoNumber}: Partial Receipt Flow");

        // Create confirmed purchase order with unique number
        $uniqueSuffix = strtoupper(Str::random(8));
        $purchaseOrder = PurchaseOrder::factory()->confirmed()->create([
            'supplier_id' => $supplier->supplier_id,
            'purchase_order_number' => "PO-DEMO-{$demoNumber}-002-{$uniqueSuffix}",
            
            'created_by_user_id' => $user->user_id,
        ]);
        

        // Add items
        $item = PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $purchaseOrder->purchase_order_id,
            'product_id' => $product->product_id,
            'quantity' => 50,
            'unit_price' => $product->cost,
            'item_total' => 50 * $product->cost,
        ]);

        $purchaseOrder->updateQuietly([
            'subtotal' => $item->item_total,
            'total_amount' => $item->item_total + 150,
        ]);

        $this->command->info("  ✓ Created confirmed PO: {$purchaseOrder->purchase_order_number}");

        // Mark as dispatched
        $purchaseOrder->markAsReadyForDispatch();
        $purchaseOrder->markAsDispatched();
        $this->command->info("  ✓ Dispatched: Status = {$purchaseOrder->status}");

        // Create partial goods receipt
        $goodsReceipt = GoodsReceipt::factory()->create([
            'purchase_order_id' => $purchaseOrder->purchase_order_id,
            'received_by_user_id' => $user->user_id,
            'warehouse_id' => $warehouse->warehouse_id,
            'receipt_number' => $faker->unique()->numerify("GR-{$demoNumber}-###"),
            'status' => 'draft',
        ]);

        // Add partial receipt items
        $receiptItem = GoodsReceiptItem::factory()->create([
            'goods_receipt_id' => $goodsReceipt->goods_receipt_id,
            'purchase_order_item_id' => $item->purchase_order_item_id,
            'product_id' => $product->product_id,
            'quantity_received' => 30, // Partial receipt
            'unit_cost_with_landed' => $product->cost + 15,
            'total_cost' => 30 * ($product->cost + 15),
        ]);

        // Process receipt
        $goodsReceipt->processReceipt();
        $this->command->info("  ✓ Partial receipt processed: Status = {$purchaseOrder->status}");
        $this->command->info("  ✓ Product quantity: {$product->fresh()->quantity_on_hand}/50");
        $this->command->info("---");
    }

    private function createCancelledOrder($user, $supplier, $product, $demoNumber)
    {
        $this->command->info("Demo {$demoNumber}: Cancelled Order");

        // Create draft purchase order with unique number
        $uniqueSuffix = strtoupper(Str::random(5));
        $purchaseOrder = PurchaseOrder::factory()->create([
            'supplier_id' => $supplier->supplier_id,
            'purchase_order_number' => "PO-DEMO-{$demoNumber}-003-{$uniqueSuffix}",
            'status' => 'draft',
            'created_by_user_id' => $user->user_id,
        ]);

        $this->command->info("  ✓ Created draft PO: {$purchaseOrder->purchase_order_number}");

        // Cancel the order
        $purchaseOrder->status = 'cancelled';
        $purchaseOrder->save();

        $this->command->info("  ✓ Cancelled PO: Status = {$purchaseOrder->status}");
        $this->command->info("  ✓ Cannot receive payments: " . ($purchaseOrder->canReceivePayments() ? 'Yes' : 'No'));
        $this->command->info("---");
    }
} 