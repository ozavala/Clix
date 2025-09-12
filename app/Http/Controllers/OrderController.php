<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Customer;
use App\Models\Quotation;
use App\Models\Opportunity;
use App\Models\Product;
use App\Models\Address;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Order::with(['customer', 'quotation', 'opportunity'])->latest();

        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('order_number', 'like', "%{$searchTerm}%")
                  ->orWhereHas('customer', fn($cq) => 
                        $cq->where('first_name', 'like', "%{$searchTerm}%")
                           ->orWhere('last_name', 'like', "%{$searchTerm}%")
                    );
            });
        }
        if ($request->filled('status_filter')) {
            $query->where('status', $request->input('status_filter'));
        }

        $orders = $query->paginate(10)->withQueryString();
        $statuses = Order::$statuses;
        return view('orders.index', compact('orders', 'statuses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $statuses = Order::$statuses;
        $customers = Customer::orderBy('first_name')->orderBy('last_name')->get();
        $quotations = Quotation::where('status', 'Accepted')->orderBy('subject')->get(); // Or other relevant statuses
        $opportunities = Opportunity::where('stage', 'Closed Won')->orderBy('name')->get(); // Or other relevant stages
        $products = Product::where('is_active', true)->orderBy('name')->get();
        
        $selectedQuotationId = $request->query('quotation_id');
        $selectedCustomerId = $request->query('customer_id');
        $selectedOpportunityId = $request->query('opportunity_id');
        $customerAddresses = [];

        if ($selectedCustomerId) {
            $customer = Customer::find($selectedCustomerId);
            if ($customer) {
                $customerAddresses = $customer->addresses()->get();
            }
        } elseif ($selectedQuotationId) {
            $quotation = Quotation::with('opportunity.customer.addresses')->find($selectedQuotationId);
            if ($quotation && $quotation->opportunity && $quotation->opportunity->customer) {
                $selectedCustomerId = $quotation->opportunity->customer_id;
                $customerAddresses = $quotation->opportunity->customer->addresses;
            }
        }

        return view('orders.create', compact('statuses', 'customers', 'quotations', 'opportunities', 'products', 'selectedQuotationId', 'selectedCustomerId', 'selectedOpportunityId', 'customerAddresses'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderRequest $request)
    {
        $validatedData = $request->validated();
        
        return DB::transaction(function () use ($validatedData) {
            $orderData = collect($validatedData)->except(['items'])->all();
            $orderData['created_by_user_id'] = Auth::id();
            // Ensure tenant_id is present
            if (empty($orderData['tenant_id'])) {
                $orderData['tenant_id'] = Auth::user()->tenant_id ?? config('tenant_id');
            }
            $orderData['order_number'] = $orderData['order_number'] ?? ('ORD-' . strtoupper(Str::random(8)));
            
            $totals = $this->calculateTotals(
                $validatedData['items'] ?? [],
                $validatedData['discount_type'] ?? null,
                $validatedData['discount_value'] ?? 0,
                $validatedData['tax_percentage'] ?? 0
            );
            $orderData = array_merge($orderData, $totals);

            $order = Order::create($orderData);

            foreach ($validatedData['items'] ?? [] as $itemData) {
                $itemData['item_total'] = ($itemData['quantity'] ?? 0) * ($itemData['unit_price'] ?? 0);
                $order->items()->create($itemData);
            }

            return redirect()->route('orders.index')
                             ->with('success', 'Order created successfully.');
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        $order->load(['customer', 'quotation', 'opportunity', 'createdBy', 'items.product', 'payments.createdBy', 'invoices']);
        // If using Address model for shipping/billing:
        // $order->load(['shippingAddress', 'billingAddress']);
        return view('orders.show', compact('order'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Order $order)
    {
        $statuses = Order::$statuses;
        $customers = Customer::orderBy('first_name')->orderBy('last_name')->get();
        $quotations = Quotation::orderBy('subject')->get();
        $opportunities = Opportunity::orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $order->load('items', 'customer.addresses');
        $customerAddresses = $order->customer ? $order->customer->addresses : collect();

        return view('orders.edit', compact('order', 'statuses', 'customers', 'quotations', 'opportunities', 'products', 'customerAddresses'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderRequest $request, Order $order)
    {
        $validatedData = $request->validated();

        return DB::transaction(function () use ($validatedData, $order) {
            $orderData = collect($validatedData)->except(['items'])->all();
            $orderData['order_number'] = $orderData['order_number'] ?? $order->order_number ?? ('ORD-' . strtoupper(Str::random(8)));

            $totals = $this->calculateTotals(
                $validatedData['items'] ?? [],
                $validatedData['discount_type'] ?? null,
                $validatedData['discount_value'] ?? 0,
                $validatedData['tax_percentage'] ?? 0
            );
            $orderData = array_merge($orderData, $totals);
            
            $order->update($orderData);

            $existingItemIds = $order->items->pluck('order_item_id')->all();
            $newItemIds = [];

            foreach ($validatedData['items'] ?? [] as $itemData) {
                $itemData['item_total'] = ($itemData['quantity'] ?? 0) * ($itemData['unit_price'] ?? 0);
                if (isset($itemData['order_item_id']) && in_array($itemData['order_item_id'], $existingItemIds)) {
                    $item = $order->items()->find($itemData['order_item_id']);
                    $item->update($itemData);
                    $newItemIds[] = $item->order_item_id;
                } else {
                    $newItem = $order->items()->create($itemData);
                    $newItemIds[] = $newItem->order_item_id;
                }
            }
            $itemsToDelete = array_diff($existingItemIds, $newItemIds);
            if (!empty($itemsToDelete)) {
                $order->items()->whereIn('order_item_id', $itemsToDelete)->delete();
            }

            return redirect()->route('orders.index')
                             ->with('success', 'Order updated successfully.');
        });
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        // Add checks if order is linked to invoices, payments etc.
        $order->items()->delete();
        $order->delete();
        return redirect()->route('orders.index')
                         ->with('success', 'Order deleted successfully.');
    }

    protected function calculateTotals(array $items, ?string $discountType, float $discountValue, float $taxPercentage): array
    {
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0);
        }

        $discountAmount = 0.00;
        if ($discountValue > 0) {
            if ($discountType === 'percentage') {
                $discountAmount = ($subtotal * $discountValue) / 100;
            } elseif ($discountType === 'fixed') {
                $discountAmount = $discountValue;
            }
        }

        $subtotalAfterDiscount = $subtotal - $discountAmount;

        $taxAmount = 0.00;
        if ($taxPercentage > 0) {
            $taxAmount = ($subtotalAfterDiscount * $taxPercentage) / 100;
        }
        $totalAmount = $subtotalAfterDiscount + $taxAmount;

        return [
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
        ];
    }
}