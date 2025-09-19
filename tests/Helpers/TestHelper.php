<?php

namespace Tests\Helpers;

use App\Models\Address;
use App\Models\Bill;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\UserRole;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TestHelper
{
    use RefreshDatabase;

    /**
     * Create a customer with orders and contacts
     */
    public static function createCustomerWithOrders(int $orderCount = 3): Customer
    {
        return Customer::factory()
            ->has(Order::factory()->count($orderCount))
            ->has(Contact::factory()->count(2))
            ->create();
    }

    /**
     * Create a product with inventory
     */
    public static function createProductWithInventory(): Product
    {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();
        
        $product->warehouses()->attach($warehouse->warehouse_id, [
            'quantity' => fake()->numberBetween(10, 100)
        ]);

        return $product;
    }

    /**
     * Create a user with specific role
     */
    public static function createUserWithRole(string $roleName = 'admin'): User
    {
        $role = UserRole::factory()->create(['name' => $roleName]);
        $user = User::factory()->create();
        
        $user->roles()->attach($role->user_role_id);
        
        return $user;
    }

    /**
     * Create a complete sales process
     */
    public static function createSalesProcess(): array
    {
        $customer = Customer::factory()->create();
        $user = User::factory()->create();
        $product = Product::factory()->create();
        
        $order = Order::factory()
            ->for($customer)
            ->for($user)
            ->create(['status' => 'confirmed']);

        $invoice = Invoice::factory()
            ->for($customer)
            ->for($user)
            ->create(['status' => 'sent']);

        return [
            'customer' => $customer,
            'user' => $user,
            'product' => $product,
            'order' => $order,
            'invoice' => $invoice,
        ];
    }

    /**
     * Create a complete purchase process
     */
    public static function createPurchaseProcess(): array
    {
        $supplier = Supplier::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();
        
        $purchaseOrder = PurchaseOrder::factory()
            ->for($supplier)
            ->create(['status' => 'confirmed']);

        $bill = Bill::factory()
            ->for($supplier)
            ->create(['status' => 'sent']);

        return [
            'supplier' => $supplier,
            'warehouse' => $warehouse,
            'product' => $product,
            'purchaseOrder' => $purchaseOrder,
            'bill' => $bill,
        ];
    }

    /**
     * Create a customer with address
     */
    public static function createCustomerWithAddress(): Customer
    {
        $customer = Customer::factory()->create();
        
        Address::factory()->create([
            'addressable_type' => Customer::class,
            'addressable_id' => $customer->customer_id,
        ]);

        return $customer;
    }

    /**
     * Create a payment for an invoice
     */
    public static function createPaymentForInvoice(Invoice $invoice, float $amount = null): Payment
    {
        return Payment::factory()->create([
            'payable_type' => Invoice::class,
            'payable_id' => $invoice->invoice_id,
            'amount' => $amount ?? $invoice->total_amount,
        ]);
    }

    /**
     * Create a payment for a bill
     */
    public static function createPaymentForBill(Bill $bill, float $amount = null): Payment
    {
        return Payment::factory()->create([
            'payable_type' => Bill::class,
            'payable_id' => $bill->bill_id,
            'amount' => $amount ?? $bill->total_amount,
        ]);
    }
} 