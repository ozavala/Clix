<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\CrmUser;
use App\Models\UserRole;
use App\Models\Warehouse;
use App\Models\Supplier;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Lead;
use App\Models\Opportunity;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SimpleReportDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌱 Seeding simple report data...');

        // Crear categorías de productos
        $this->createProductCategories();
        
        // Crear productos
        $this->createProducts();
        
        // Crear warehouses
        $this->createWarehouses();
        
        // Crear clientes
        $this->createCustomers();
        
        // Crear proveedores
        $this->createSuppliers();
        
        // Crear leads y oportunidades
        $this->createLeadsAndOpportunities();
        
        // Crear cotizaciones
        $this->createQuotations();
        
        // Crear órdenes de compra
        $this->createPurchaseOrders();
        
        // Crear órdenes de venta
        $this->createOrders();
        
        // Crear facturas
        $this->createInvoices();
        
        // Crear pagos
        $this->createPayments();
        
        // Asignar stock a productos
        $this->assignProductStock();
        
        $this->command->info('✅ Simple report data seeded successfully!');
    }

    private function createProductCategories(): void
    {
        $categories = [
            ['name' => 'Electronics', 'description' => 'Electronic devices and accessories'],
            ['name' => 'Clothing', 'description' => 'Apparel and fashion items'],
            ['name' => 'Home & Garden', 'description' => 'Home improvement and garden products'],
            ['name' => 'Sports & Outdoors', 'description' => 'Sports equipment and outdoor gear'],
            ['name' => 'Books & Media', 'description' => 'Books, movies, and media'],
            ['name' => 'Automotive', 'description' => 'Automotive parts and accessories'],
            ['name' => 'Health & Beauty', 'description' => 'Health and beauty products'],
            ['name' => 'Toys & Games', 'description' => 'Toys and entertainment products'],
        ];

        foreach ($categories as $category) {
            ProductCategory::firstOrCreate(['name' => $category['name']], $category);
        }
    }

    private function createProducts(): void
    {
        $categories = ProductCategory::all();
        
        $products = [
            // Electronics
            ['name' => 'iPhone 15 Pro', 'sku' => 'IPH15PRO', 'price' => 999.99, 'cost' => 650.00, 'category' => 'Electronics'],
            ['name' => 'Samsung Galaxy S24', 'sku' => 'SAMS24', 'price' => 899.99, 'cost' => 580.00, 'category' => 'Electronics'],
            ['name' => 'MacBook Air M2', 'sku' => 'MBAIRM2', 'price' => 1199.99, 'cost' => 800.00, 'category' => 'Electronics'],
            ['name' => 'Dell XPS 13', 'sku' => 'DELLXPS13', 'price' => 1099.99, 'cost' => 720.00, 'category' => 'Electronics'],
            ['name' => 'AirPods Pro', 'sku' => 'AIRPODSPRO', 'price' => 249.99, 'cost' => 150.00, 'category' => 'Electronics'],
            
            // Clothing
            ['name' => 'Nike Air Max', 'sku' => 'NIKEAIRMAX', 'price' => 129.99, 'cost' => 65.00, 'category' => 'Clothing'],
            ['name' => 'Adidas Ultraboost', 'sku' => 'ADIDASUB', 'price' => 179.99, 'cost' => 90.00, 'category' => 'Clothing'],
            ['name' => 'Levi\'s 501 Jeans', 'sku' => 'LEVIS501', 'price' => 89.99, 'cost' => 45.00, 'category' => 'Clothing'],
            ['name' => 'H&M T-Shirt', 'sku' => 'HMTEE', 'price' => 19.99, 'cost' => 8.00, 'category' => 'Clothing'],
            ['name' => 'Zara Blazer', 'sku' => 'ZARABLAZER', 'price' => 159.99, 'cost' => 80.00, 'category' => 'Clothing'],
            
            // Home & Garden
            ['name' => 'IKEA Desk', 'sku' => 'IKEADESK', 'price' => 199.99, 'cost' => 120.00, 'category' => 'Home & Garden'],
            ['name' => 'Garden Hose', 'sku' => 'GARDENHOSE', 'price' => 39.99, 'cost' => 20.00, 'category' => 'Home & Garden'],
            ['name' => 'LED Light Bulbs', 'sku' => 'LEDBULBS', 'price' => 24.99, 'cost' => 12.00, 'category' => 'Home & Garden'],
            ['name' => 'Kitchen Mixer', 'sku' => 'KITCHMIXER', 'price' => 89.99, 'cost' => 45.00, 'category' => 'Home & Garden'],
            ['name' => 'Coffee Maker', 'sku' => 'COFFEEMAKER', 'price' => 149.99, 'cost' => 75.00, 'category' => 'Home & Garden'],
            
            // Sports & Outdoors
            ['name' => 'Yoga Mat', 'sku' => 'YOGAMAT', 'price' => 29.99, 'cost' => 15.00, 'category' => 'Sports & Outdoors'],
            ['name' => 'Tennis Racket', 'sku' => 'TENNISRACKET', 'price' => 79.99, 'cost' => 40.00, 'category' => 'Sports & Outdoors'],
            ['name' => 'Camping Tent', 'sku' => 'CAMPTENT', 'price' => 199.99, 'cost' => 100.00, 'category' => 'Sports & Outdoors'],
            ['name' => 'Bicycle Helmet', 'sku' => 'BIKEHELMET', 'price' => 59.99, 'cost' => 30.00, 'category' => 'Sports & Outdoors'],
            ['name' => 'Fitness Tracker', 'sku' => 'FITTRACKER', 'price' => 99.99, 'cost' => 50.00, 'category' => 'Sports & Outdoors'],
            
            // Books & Media
            ['name' => 'Harry Potter Set', 'sku' => 'HARRYPOTTER', 'price' => 89.99, 'cost' => 45.00, 'category' => 'Books & Media'],
            ['name' => 'Bluetooth Speaker', 'sku' => 'BTSPEAKER', 'price' => 79.99, 'cost' => 40.00, 'category' => 'Books & Media'],
            ['name' => 'Kindle Paperwhite', 'sku' => 'KINDLEPW', 'price' => 139.99, 'cost' => 70.00, 'category' => 'Books & Media'],
            ['name' => 'Gaming Headset', 'sku' => 'GAMEHEADSET', 'price' => 119.99, 'cost' => 60.00, 'category' => 'Books & Media'],
            ['name' => 'Board Game Collection', 'sku' => 'BOARDGAMES', 'price' => 49.99, 'cost' => 25.00, 'category' => 'Books & Media'],
        ];

        foreach ($products as $productData) {
            $category = ProductCategory::where('name', $productData['category'])->first();
            
            Product::firstOrCreate(
                ['sku' => $productData['sku']],
                [
                    'name' => $productData['name'],
                    'sku' => $productData['sku'],
                    'price' => $productData['price'],
                    'cost' => $productData['cost'],
                    'category_id' => $category->id,
                    'description' => 'High quality ' . strtolower($productData['name']),
                    'reorder_point' => rand(5, 20),
                ]
            );
        }
    }

    private function createWarehouses(): void
    {
        $warehouses = [
            ['name' => 'Main Warehouse', 'address' => '123 Main St, City, State 12345'],
            ['name' => 'East Coast Distribution', 'address' => '456 East Ave, City, State 12345'],
            ['name' => 'West Coast Distribution', 'address' => '789 West Blvd, City, State 12345'],
        ];

        foreach ($warehouses as $warehouse) {
            Warehouse::firstOrCreate(['name' => $warehouse['name']], $warehouse);
        }
    }

    private function createCustomers(): void
    {
        $customers = [
            ['name' => 'Tech Solutions Inc', 'email' => 'contact@techsolutions.com', 'phone' => '+1-555-0101'],
            ['name' => 'Fashion Forward LLC', 'email' => 'info@fashionforward.com', 'phone' => '+1-555-0102'],
            ['name' => 'Home Improvement Co', 'email' => 'sales@homeimprovement.com', 'phone' => '+1-555-0103'],
            ['name' => 'Sports Equipment Ltd', 'email' => 'orders@sportsequipment.com', 'phone' => '+1-555-0104'],
            ['name' => 'Media Store', 'email' => 'customerservice@mediastore.com', 'phone' => '+1-555-0105'],
            ['name' => 'Auto Parts Express', 'email' => 'sales@autopartsexpress.com', 'phone' => '+1-555-0106'],
            ['name' => 'Beauty Supply Co', 'email' => 'orders@beautysupply.com', 'phone' => '+1-555-0107'],
            ['name' => 'Toy World', 'email' => 'info@toyworld.com', 'phone' => '+1-555-0108'],
            ['name' => 'Office Supplies Plus', 'email' => 'sales@officesupplies.com', 'phone' => '+1-555-0109'],
            ['name' => 'Pet Supplies Unlimited', 'email' => 'orders@petsupplies.com', 'phone' => '+1-555-0110'],
        ];

        foreach ($customers as $customer) {
            Customer::firstOrCreate(['email' => $customer['email']], $customer);
        }
    }

    private function createSuppliers(): void
    {
        $suppliers = [
            ['name' => 'Apple Inc', 'email' => 'supplier@apple.com', 'phone' => '+1-555-0201'],
            ['name' => 'Samsung Electronics', 'email' => 'orders@samsung.com', 'phone' => '+1-555-0202'],
            ['name' => 'Nike Inc', 'email' => 'supplier@nike.com', 'phone' => '+1-555-0203'],
            ['name' => 'Adidas AG', 'email' => 'orders@adidas.com', 'phone' => '+1-555-0204'],
            ['name' => 'IKEA Group', 'email' => 'supplier@ikea.com', 'phone' => '+1-555-0205'],
            ['name' => 'Dell Technologies', 'email' => 'orders@dell.com', 'phone' => '+1-555-0206'],
            ['name' => 'Sony Corporation', 'email' => 'supplier@sony.com', 'phone' => '+1-555-0207'],
            ['name' => 'LG Electronics', 'email' => 'orders@lg.com', 'phone' => '+1-555-0208'],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::firstOrCreate(['email' => $supplier['email']], $supplier);
        }
    }

    private function createLeadsAndOpportunities(): void
    {
        $leads = [
            ['name' => 'New Tech Startup', 'email' => 'contact@newtechstartup.com', 'phone' => '+1-555-0301'],
            ['name' => 'Fashion Boutique', 'email' => 'info@fashionboutique.com', 'phone' => '+1-555-0302'],
            ['name' => 'Gym Equipment Store', 'email' => 'sales@gymequipment.com', 'phone' => '+1-555-0303'],
            ['name' => 'Online Bookstore', 'email' => 'orders@onlinebookstore.com', 'phone' => '+1-555-0304'],
            ['name' => 'Auto Repair Shop', 'email' => 'service@autorepair.com', 'phone' => '+1-555-0305'],
        ];

        foreach ($leads as $leadData) {
            $lead = Lead::firstOrCreate(
                ['email' => $leadData['email']],
                [
                    'name' => $leadData['name'],
                    'email' => $leadData['email'],
                    'phone' => $leadData['phone'],
                    'status' => 'new',
                    'source' => 'website',
                ]
            );

            // Crear oportunidad para algunos leads
            if (rand(1, 3) === 1) {
                Opportunity::firstOrCreate(
                    ['lead_id' => $lead->id],
                    [
                        'lead_id' => $lead->id,
                        'title' => 'Potential ' . $lead->name . ' Deal',
                        'amount' => rand(5000, 50000),
                        'stage' => 'prospecting',
                        'probability' => rand(10, 90),
                    ]
                );
            }
        }
    }

    private function createQuotations(): void
    {
        $customers = Customer::all();
        $products = Product::all();

        for ($i = 0; $i < 15; $i++) {
            $customer = $customers->random();
            
            $quotation = Quotation::create([
                'customer_id' => $customer->id,
                'quotation_number' => 'QT-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'quotation_date' => now()->subDays(rand(1, 30)),
                'expiry_date' => now()->addDays(rand(7, 30)),
                'status' => ['draft', 'sent', 'accepted', 'rejected'][rand(0, 3)],
                'subtotal' => 0,
                'tax_rate' => 8.5,
                'tax_amount' => 0,
                'discount_rate' => rand(0, 15),
                'discount_amount' => 0,
                'total_amount' => 0,
            ]);

            // Crear items de cotización
            $numItems = rand(1, 5);
            $subtotal = 0;
            
            for ($j = 0; $j < $numItems; $j++) {
                $product = $products->random();
                $quantity = rand(1, 10);
                $unitPrice = $product->price * (1 - rand(5, 20) / 100); // Descuento del 5-20%
                $itemTotal = $quantity * $unitPrice;
                $subtotal += $itemTotal;

                QuotationItem::create([
                    'quotation_id' => $quotation->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $itemTotal,
                ]);
            }

            // Actualizar totales
            $discountAmount = $subtotal * ($quotation->discount_rate / 100);
            $taxAmount = ($subtotal - $discountAmount) * ($quotation->tax_rate / 100);
            $totalAmount = $subtotal - $discountAmount + $taxAmount;

            $quotation->update([
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
            ]);
        }
    }

    private function createPurchaseOrders(): void
    {
        $suppliers = Supplier::all();
        $products = Product::all();

        for ($i = 0; $i < 20; $i++) {
            $supplier = $suppliers->random();
            
            $purchaseOrder = PurchaseOrder::create([
                'supplier_id' => $supplier->id,
                'po_number' => 'PO-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'order_date' => now()->subDays(rand(1, 60)),
                'expected_delivery_date' => now()->addDays(rand(7, 30)),
                'status' => ['draft', 'confirmed', 'dispatched', 'partially_received', 'fully_received', 'paid'][rand(0, 5)],
                'subtotal' => 0,
                'tax_rate' => 8.5,
                'tax_amount' => 0,
                'shipping_cost' => rand(50, 200),
                'total_amount' => 0,
            ]);

            // Crear items de orden de compra
            $numItems = rand(2, 8);
            $subtotal = 0;
            
            for ($j = 0; $j < $numItems; $j++) {
                $product = $products->random();
                $quantity = rand(10, 100);
                $unitPrice = $product->cost * (1 + rand(5, 25) / 100); // Margen del 5-25%
                $itemTotal = $quantity * $unitPrice;
                $subtotal += $itemTotal;

                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $itemTotal,
                ]);
            }

            // Actualizar totales
            $taxAmount = $subtotal * ($purchaseOrder->tax_rate / 100);
            $totalAmount = $subtotal + $taxAmount + $purchaseOrder->shipping_cost;

            $purchaseOrder->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
            ]);
        }
    }

    private function createOrders(): void
    {
        $customers = Customer::all();
        $products = Product::all();

        for ($i = 0; $i < 50; $i++) {
            $customer = $customers->random();
            
            $order = Order::create([
                'customer_id' => $customer->id,
                'order_number' => 'ORD-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'order_date' => now()->subDays(rand(1, 90)),
                'status' => ['pending', 'processing', 'shipped', 'delivered', 'cancelled'][rand(0, 4)],
                'subtotal' => 0,
                'tax_rate' => 8.5,
                'tax_amount' => 0,
                'shipping_cost' => rand(10, 50),
                'total_amount' => 0,
            ]);

            // Crear items de orden
            $numItems = rand(1, 5);
            $subtotal = 0;
            
            for ($j = 0; $j < $numItems; $j++) {
                $product = $products->random();
                $quantity = rand(1, 10);
                $unitPrice = $product->price;
                $itemTotal = $quantity * $unitPrice;
                $subtotal += $itemTotal;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $itemTotal,
                ]);
            }

            // Actualizar totales
            $taxAmount = $subtotal * ($order->tax_rate / 100);
            $totalAmount = $subtotal + $taxAmount + $order->shipping_cost;

            $order->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
            ]);
        }
    }

    private function createInvoices(): void
    {
        $orders = Order::whereIn('status', ['delivered', 'shipped'])->get();
        $customers = Customer::all();
        $products = Product::all();

        // Crear facturas basadas en órdenes
        foreach ($orders as $order) {
            $invoice = Invoice::create([
                'customer_id' => $order->customer_id,
                'invoice_number' => 'INV-' . str_pad($order->id, 4, '0', STR_PAD_LEFT),
                'invoice_date' => $order->order_date->addDays(rand(1, 7)),
                'due_date' => $order->order_date->addDays(rand(15, 30)),
                'status' => ['draft', 'sent', 'partially_paid', 'paid', 'overdue'][rand(0, 4)],
                'subtotal' => $order->subtotal,
                'tax_rate' => $order->tax_rate,
                'tax_amount' => $order->tax_amount,
                'shipping_cost' => $order->shipping_cost,
                'total_amount' => $order->total_amount,
            ]);

            // Crear items de factura basados en items de orden
            foreach ($order->items as $orderItem) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $orderItem->product_id,
                    'quantity' => $orderItem->quantity,
                    'unit_price' => $orderItem->unit_price,
                    'total' => $orderItem->total,
                ]);
            }
        }

        // Crear algunas facturas adicionales sin orden
        for ($i = 0; $i < 10; $i++) {
            $customer = $customers->random();
            
            $invoice = Invoice::create([
                'customer_id' => $customer->id,
                'invoice_number' => 'INV-DIRECT-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'invoice_date' => now()->subDays(rand(1, 60)),
                'due_date' => now()->addDays(rand(15, 30)),
                'status' => ['draft', 'sent', 'partially_paid', 'paid', 'overdue'][rand(0, 4)],
                'subtotal' => 0,
                'tax_rate' => 8.5,
                'tax_amount' => 0,
                'shipping_cost' => rand(10, 50),
                'total_amount' => 0,
            ]);

            // Crear items de factura
            $numItems = rand(1, 4);
            $subtotal = 0;
            
            for ($j = 0; $j < $numItems; $j++) {
                $product = $products->random();
                $quantity = rand(1, 8);
                $unitPrice = $product->price;
                $itemTotal = $quantity * $unitPrice;
                $subtotal += $itemTotal;

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $itemTotal,
                ]);
            }

            // Actualizar totales
            $taxAmount = $subtotal * ($invoice->tax_rate / 100);
            $totalAmount = $subtotal + $taxAmount + $invoice->shipping_cost;

            $invoice->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
            ]);
        }
    }

    private function createPayments(): void
    {
        $invoices = Invoice::whereIn('status', ['sent', 'partially_paid'])->get();
        $purchaseOrders = PurchaseOrder::whereIn('status', ['confirmed', 'dispatched', 'fully_received'])->get();

        // Crear pagos para facturas
        foreach ($invoices as $invoice) {
            $paymentAmount = $invoice->amount_due;
            if ($invoice->status === 'partially_paid') {
                $paymentAmount = $paymentAmount * rand(30, 80) / 100; // Pago parcial
            }

            if ($paymentAmount > 0) {
                Payment::create([
                    'payable_type' => Invoice::class,
                    'payable_id' => $invoice->id,
                    'amount' => $paymentAmount,
                    'payment_date' => $invoice->invoice_date->addDays(rand(1, 30)),
                    'payment_method' => ['credit_card', 'bank_transfer', 'cash', 'check'][rand(0, 3)],
                    'reference_number' => 'PAY-' . str_pad($invoice->id, 4, '0', STR_PAD_LEFT),
                    'notes' => 'Payment for invoice ' . $invoice->invoice_number,
                ]);
            }
        }

        // Crear pagos para órdenes de compra
        foreach ($purchaseOrders as $po) {
            $paymentAmount = $po->amount_due;
            if ($paymentAmount > 0) {
                Payment::create([
                    'payable_type' => PurchaseOrder::class,
                    'payable_id' => $po->id,
                    'amount' => $paymentAmount,
                    'payment_date' => $po->order_date->addDays(rand(30, 60)),
                    'payment_method' => ['bank_transfer', 'check', 'credit_card'][rand(0, 2)],
                    'reference_number' => 'PAY-PO-' . str_pad($po->id, 4, '0', STR_PAD_LEFT),
                    'notes' => 'Payment for purchase order ' . $po->po_number,
                ]);
            }
        }
    }

    private function assignProductStock(): void
    {
        $products = Product::all();
        $warehouses = Warehouse::all();

        foreach ($products as $product) {
            foreach ($warehouses as $warehouse) {
                $quantity = rand(0, 200);
                $product->warehouses()->syncWithoutDetaching([
                    $warehouse->id => ['quantity' => $quantity]
                ]);
            }
        }
    }
} 