<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Address;
use App\Http\Requests\StorePurchaseOrderRequest;
use App\Http\Requests\UpdatePurchaseOrderRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

class PurchaseOrderController extends TenantAwareController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $isSuper = $user && (bool) ($user->is_super_admin ?? false);
        $requestedTenantId = $request->input('tenant_id');

        if ($isSuper) {
            $query = PurchaseOrder::withoutGlobalScopes()->with(['supplier', 'shippingAddress'])->latest();
            if ($requestedTenantId) {
                $query->where('tenant_id', $requestedTenantId);
            }
        } else {
            $query = PurchaseOrder::with(['supplier', 'shippingAddress'])->latest();
        }

        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('purchase_order_number', 'like', "%{$searchTerm}%")
                  ->orWhereHas('supplier', fn($sq) => $sq->where('name', 'like', "%{$searchTerm}%"));
            });
        }
        if ($request->filled('status_filter')) {
            $query->where('status', $request->input('status_filter'));
        }
        if ($request->filled('type_filter')) {
            $query->where('type', $request->input('type_filter'));
        }

        $purchaseOrders = $query->paginate(10)->withQueryString();
        $statuses = PurchaseOrder::$statuses;
        $types = PurchaseOrder::$types;
        if ($isSuper) {
            $tenants = \App\Models\Tenant::orderBy('name')->get(['id','name']);
            return view('purchase_orders.index', compact('purchaseOrders', 'statuses', 'types', 'tenants', 'requestedTenantId'));
        }
        return view('purchase_orders.index', compact('purchaseOrders', 'statuses', 'types'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $statuses = PurchaseOrder::$statuses;
            $types = PurchaseOrder::$types;
            $suppliers = Supplier::orderBy('name')->get();
            $products = Product::where('is_active', true)->orderBy('name')->get();
            // Assuming your company addresses might be identified by being linked to Warehouses
            // or having a specific address_type, or being unlinked (addressable_type is null).
            // This logic might need refinement based on how you store company addresses.
            $companyAddresses = Address::where('addressable_type', \App\Models\Warehouse::class) // Example: Addresses linked to Warehouses
                                ->orWhereNull('addressable_type') // Example: Generic company addresses
                                ->orderBy('street_address_line_1')
                                ->get();

            $purchaseOrder = new PurchaseOrder(['purchase_order_number' => 'PO-' . strtoupper(Str::random(8))]);
            return view('purchase_orders.create', compact('purchaseOrder', 'suppliers', 'products', 'companyAddresses', 'statuses', 'types'));
        }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePurchaseOrderRequest $request)
    {
        $validatedData = $request->validated();
            
            return DB::transaction(function () use ($validatedData) {
                $poData = collect($validatedData)->except(['items'])->all();
                $poData['created_by_user_id'] = Auth::id();
                $poData['purchase_order_number'] = $poData['purchase_order_number'] ?? ('PO-' . strtoupper(Str::random(8)));
                $poData['amount_paid'] = 0; // Initialize amount_paid

                $totals = $this->calculateTotals(
                    $validatedData['items'] ?? [],
                    $validatedData['discount_type'] ?? null,
                    $validatedData['discount_value'] ?? 0,
                    $validatedData['tax_percentage'] ?? 0,
                    $validatedData['shipping_cost'] ?? 0,
                    $validatedData['other_charges'] ?? 0
                );
                $poData = array_merge($poData, $totals);

                $purchaseOrder = PurchaseOrder::create($poData);            
                foreach ($validatedData['items'] ?? [] as $itemData) {
                    $itemData['item_total'] = ($itemData['quantity'] ?? 0) * ($itemData['unit_price'] ?? 0);
                    $purchaseOrder->items()->create($itemData);
                }

                return redirect()->route('purchase-orders.index')
                                 ->with('success', 'Purchase Order created successfully.');
            });
        
    }

    /**
     * Display the specified resource.
     */
    public function show(PurchaseOrder $purchaseOrder)
        {
            $purchaseOrder->load(['supplier', 'createdBy', 'items.product', 'shippingAddress', 'payments.createdBy', 'landedCosts', 'goodsReceipts.receivedBy']);
            return view('purchase_orders.show', compact('purchaseOrder'));
        }

    /**
     * Generate a PDF for the specified purchase order.
     *
     * @param  \App\Models\PurchaseOrder  $purchaseOrder
     * @return \Illuminate\Http\Response
     */
    public function printPdf(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['supplier.addresses', 'items.product', 'shippingAddress']);

        $logoPath = config('settings.company_logo');
        $logoData = null;
        // Use the Storage facade to get the path, which is more robust than relying on the public symlink.
        if ($logoPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($logoPath)) {
            $path = \Illuminate\Support\Facades\Storage::disk('public')->path($logoPath);
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);
            // It's crucial to use base64 encoding for embedding images in dompdf
            $logoData = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        $pdf = Pdf::loadView('purchase_orders.pdf', compact('purchaseOrder', 'logoData'));
        return $pdf->stream('po_' . $purchaseOrder->purchase_order_number . '.pdf');
    }

    /**
     * Show the form for editing the specified resource.
     */
   public function edit(PurchaseOrder $purchaseOrder)
        {
            $statuses = PurchaseOrder::$statuses;
            $types = PurchaseOrder::$types;
            $suppliers = Supplier::orderBy('name')->get();
            $products = Product::where('is_active', true)->orderBy('name')->get();
            $purchaseOrder->load('items');
            $companyAddresses = Address::where('addressable_type', \App\Models\Warehouse::class)
                                ->orWhereNull('addressable_type')
                                ->orderBy('street_address_line_1')
                                ->get();

            return view('purchase_orders.edit', compact('purchaseOrder', 'statuses', 'types', 'suppliers', 'products', 'companyAddresses'));
        }

    /**
     * Update the specified resource in storage.
     */
     public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder)
        {
            $validatedData = $request->validated();

            return DB::transaction(function () use ($validatedData, $purchaseOrder) {
                $poData = collect($validatedData)->except(['items'])->all();
                $poData['purchase_order_number'] = $poData['purchase_order_number'] ?? $purchaseOrder->purchase_order_number ?? ('PO-' . strtoupper(Str::random(8)));

                $totals = $this->calculateTotals(
                    $validatedData['items'] ?? [],
                    $validatedData['discount_type'] ?? null,
                    $validatedData['discount_value'] ?? 0,
                    $validatedData['tax_percentage'] ?? 0,
                    $validatedData['shipping_cost'] ?? 0,
                    $validatedData['other_charges'] ?? 0
                );
                $poData = array_merge($poData, $totals);
                
                $purchaseOrder->update($poData);
                
                $existingItemIds = $purchaseOrder->items->pluck('purchase_order_item_id')->all();
                $newItemIds = [];

                foreach ($validatedData['items'] ?? [] as $itemData) {
                    $itemData['item_total'] = ($itemData['quantity'] ?? 0) * ($itemData['unit_price'] ?? 0);
                    if (isset($itemData['purchase_order_item_id']) && in_array($itemData['purchase_order_item_id'], $existingItemIds)) {
                        $item = $purchaseOrder->items()->find($itemData['purchase_order_item_id']);
                        $item->update($itemData);
                        $newItemIds[] = $item->purchase_order_item_id;
                    } else {
                        $newItem = $purchaseOrder->items()->create($itemData);
                        $newItemIds[] = $newItem->purchase_order_item_id;
                    }
                }
                $itemsToDelete = array_diff($existingItemIds, $newItemIds);
                if (!empty($itemsToDelete)) {
                    $purchaseOrder->items()->whereIn('purchase_order_item_id', $itemsToDelete)->delete();
                }

                return redirect()->route('purchase-orders.index')
                                 ->with('success', 'Purchase Order updated successfully.');
            });
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PurchaseOrder $purchaseOrder)
    {
        // Add checks if PO is linked to bills, receipts etc.
        if ($purchaseOrder->payments()->exists()) {
                return redirect()->route('purchase-orders.index')
                                 ->with('error', 'Cannot delete Purchase Order with existing payments. Please delete payments first.');
            }
            $purchaseOrder->items()->delete();
            $purchaseOrder->delete();
            return redirect()->route('purchase-orders.index')
                             ->with('success', 'Purchase Order deleted successfully.');
        }

        protected function calculateTotals(
            array $items,
            ?string $discountType,
            float $discountValue,
            float $taxPercentage,
            float $shippingCost,
            float $otherCharges
        ): array
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

            $totalAmount = $subtotalAfterDiscount + $taxAmount + $shippingCost + $otherCharges;

            return [
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'shipping_cost' => $shippingCost,
                'other_charges' => $otherCharges,
                'total_amount' => $totalAmount,
            ];
        
    }
}
