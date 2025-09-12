<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\LandedCost;
use App\Models\CrmUser;
use App\Services\LandedCostService;
use Carbon\Carbon;
use Illuminate\Support\Str;

class UnitPriceCalculationSeeder extends Seeder
{
    public function run(): void
    {
        // Get or create a tenant
        $tenant = \App\Models\Tenant::firstOrCreate(
            ['id' => 1],
            ['name' => 'Test Tenant', 'domain' => 'test-tenant']
        );

        // Create a user for the process
        $user = CrmUser::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create suppliers with different characteristics
        $suppliers = [
            [
                'tenant_id' => $tenant->id,
                'name' => 'Electronics Import Co.',
                'legal_id' => 'SUP-2001',
                'email' => 'import.electronics@example.com',
                'phone_number' => '+1-555-ELECTRONICS',
                'contact_person' => 'John Electronics',
                'noteable_id' => 1,
                'noteable_type' => 'App\Models\Supplier',
            ],
            [
                'tenant_id' => 1,
                'name' => 'Raw Materials Supplier',
                'legal_id' => 'SUP-2002',
                'email' => 'supply.rawmaterials@example.com',
                'phone_number' => '+1-555-RAWMATERIALS',
                'contact_person' => 'Sarah Materials',
                'noteable_id' => 2,
                'noteable_type' => 'App\Models\Supplier',
            ],
            [
                'tenant_id' => 1,
                'name' => 'Machinery Parts Inc.',
                'legal_id' => 'SUP-2003',
                'email' => 'parts.machinery@example.com',
                'phone_number' => '+1-555-MACHINERY',
                'contact_person' => 'Mike Machinery',
                'noteable_id' => 3,
                'noteable_type' => 'App\Models\Supplier',
            ],
        ];

        foreach ($suppliers as $supplierData) {
            // Check if supplier already exists
            $supplier = Supplier::where('email', $supplierData['email'])->first();
            if (!$supplier) {
                $supplier = Supplier::create($supplierData);
            }

            // Create products for each supplier
            $products = [
                [
                    'tenant_id' => 1,
                    'name' => 'High-end Microprocessor',
                    'sku' => 'CPU-' . strtoupper(substr($supplier->name, 0, 3)) . '-001',
                    'price' => 1200.00,
                    'cost' => 800.00,
                    'description' => 'Advanced microprocessor for computing applications',
                ],
                [
                    'tenant_id' => 1,
                    'name' => 'Industrial Sensor',
                    'sku' => 'SENSOR-' . strtoupper(substr($supplier->name, 0, 3)) . '-001',
                    'price' => 450.00,
                    'cost' => 300.00,
                    'description' => 'Precision industrial sensor for automation',
                ],
                [
                    'tenant_id' => 1,
                    'name' => 'Power Supply Unit',
                    'sku' => 'PSU-' . strtoupper(substr($supplier->name, 0, 3)) . '-001',
                    'price' => 350.00,
                    'cost' => 250.00,
                    'description' => 'Reliable power supply for industrial equipment',
                ],
            ];

            foreach ($products as $productData) {
                // Check if product already exists
                $product = Product::where('sku', $productData['sku'])->first();
                if (!$product) {
                    $product = Product::create(array_merge($productData, [
                        'quantity_on_hand' => 0,
                        'is_service' => false,
                        'is_active' => true,
                        'created_by_user_id' => $user->user_id,
                    ]));
                }

                // Crear un número de orden único
                $uniqueSuffix = strtoupper(Str::random(5));
                $purchaseOrderNumber = 'PO-' . strtoupper(substr($supplier->name, 0, 3)) . '-' . $product->sku . '-' . $uniqueSuffix;

                // Create purchase order for this product
                $purchaseOrder = PurchaseOrder::create([
                    'tenant_id' => 1,
                    'supplier_id' => $supplier->supplier_id,
                    'purchase_order_number' => $purchaseOrderNumber,
                    'order_date' => Carbon::now()->subDays(rand(30, 90)),
                    'expected_delivery_date' => Carbon::now()->addDays(rand(15, 45)),
                    'type' => 'Import',
                    'status' => 'confirmed',
                    'subtotal' => 0, // Will be calculated
                    'tax_percentage' => 10,
                    'tax_amount' => 0, // Will be calculated
                    'shipping_cost' => rand(50, 200),
                    'other_charges' => 0,
                    'total_amount' => 0, // Will be calculated
                    'amount_paid' => 0,
                    'created_by_user_id' => $user->user_id,
                ]);

                // Create purchase order item
                $quantity = rand(10, 100);
                $unitPrice = $product->cost;
                $itemTotal = $quantity * $unitPrice;

                $item = PurchaseOrderItem::create([
                    'tenant_id' => $tenant->id,
                    'purchase_order_id' => $purchaseOrder->purchase_order_id,
                    'product_id' => $product->product_id,
                    'item_name' => $product->name,
                    'item_description' => $product->description,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'item_total' => $itemTotal,
                    'landed_cost_per_unit' => null, // Will be calculated
                ]);

                // Create landed costs based on supplier type
                $landedCosts = $this->getLandedCostsForSupplier($supplier->name, $itemTotal);

                foreach ($landedCosts as $cost) {
LandedCost::create([
                        'tenant_id' => $tenant->id,
                        'costable_type' => PurchaseOrder::class,
                        'costable_id' => $purchaseOrder->purchase_order_id,
                        'description' => $cost['description'],
                        'amount' => $cost['amount'],
                    ]);
                }

                // Calculate and apportion landed costs
                $landedCostService = new LandedCostService();
                $landedCostService->apportionCosts($purchaseOrder);

                // Update purchase order totals
                $item->refresh();
                $totalLandedCosts = $purchaseOrder->landedCosts()->sum('amount');
                $subtotal = $itemTotal;
                $taxAmount = ($subtotal * $purchaseOrder->tax_percentage) / 100;
                $totalAmount = $subtotal + $taxAmount + $purchaseOrder->shipping_cost + $totalLandedCosts;

                $purchaseOrder->update([
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'total_amount' => $totalAmount,
                ]);

                // Calculate final unit price including landed costs
                $finalUnitPrice = $unitPrice + $item->landed_cost_per_unit;
                
                // Update product cost with landed costs
                $product->receiveStock($quantity, $finalUnitPrice);

                $this->command->info("Created PO: {$purchaseOrder->purchase_order_number}");
                $this->command->info("  Product: {$product->name}");
                $this->command->info("  Base Cost: \${$unitPrice}");
                $this->command->info("  Landed Cost per Unit: \${$item->landed_cost_per_unit}");
                $this->command->info("  Final Unit Price: \${$finalUnitPrice}");
                $this->command->info("  Total Landed Costs: \${$totalLandedCosts}");
                $this->command->info("  Updated Product Cost: \${$product->cost}");
                $this->command->info("---");
            }
        }
    }

    private function getLandedCostsForSupplier(string $supplierName, float $itemTotal): array
    {
        $baseCosts = [
            'Freight charges' => $itemTotal * 0.08,
            'Customs duties' => $itemTotal * 0.05,
            'Insurance' => $itemTotal * 0.02,
            'Bank transfer fees' => 50.00,
            'Handling charges' => $itemTotal * 0.03,
        ];

        // Add supplier-specific costs
        if (str_contains($supplierName, 'Electronics')) {
            $baseCosts['Customs broker fees'] = 150.00;
            $baseCosts['Documentation fees'] = 75.00;
        } elseif (str_contains($supplierName, 'Raw Materials')) {
            $baseCosts['Port charges'] = 100.00;
            $baseCosts['Storage fees'] = $itemTotal * 0.01;
        } elseif (str_contains($supplierName, 'Machinery')) {
            $baseCosts['Heavy equipment handling'] = 200.00;
            $baseCosts['Specialized transport'] = $itemTotal * 0.04;
        }

        // Convertir a array de arrays con claves 'description' y 'amount'
        $costs = [];
        foreach ($baseCosts as $desc => $amount) {
            $costs[] = [
                'description' => $desc,
                'amount' => $amount,
            ];
        }
        return $costs;
    }
} 