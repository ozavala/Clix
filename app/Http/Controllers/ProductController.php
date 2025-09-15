<?php

namespace App\Http\Controllers;

use App\Models\ProductFeature; // Add this
use App\Models\ProductCategory; // Add this
use App\Models\Warehouse; // Add this
use App\Models\Product;
use App\Models\TaxRate; // Add this
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends TenantAwareController
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
            $query = Product::withoutGlobalScopes()->with(['createdBy', 'category'])->latest();
            if ($requestedTenantId) {
                $query->where('tenant_id', $requestedTenantId);
            }
        } else {
            $query = Product::with(['createdBy', 'category'])->latest();
        }

        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('sku', 'like', "%{$searchTerm}%")
                  ->orWhere('description', 'like', "%{$searchTerm}%");
            });
        }

        if ($request->filled('type_filter')) {
            $query->where('is_service', $request->input('type_filter') === 'service');
        }

        if ($request->filled('status_filter')) {
            $query->where('is_active', $request->input('status_filter') === 'active');
        }
        
        if ($request->filled('category_filter')) {
            $query->where('category_id', $request->input('category_filter'));
        }

        $products = $query->paginate(10)->withQueryString();
        $categories = ProductCategory::orderBy('name')->get(); // For filter dropdown

        if ($isSuper) {
            $tenants = \App\Models\Tenant::orderBy('name')->get(['id','name']);
            return view('products.index', compact('products', 'categories', 'tenants', 'requestedTenantId'));
        }
        return view('products.index', compact('products', 'categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $productFeatures = ProductFeature::orderBy('name')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        $categories = ProductCategory::orderBy('name')->get();
        $taxRates = TaxRate::where('is_active', true)->orderBy('rate')->get();
        return view('products.create', [
            'product' => new Product(), 
            'productFeatures' => $productFeatures, 
            'warehouses' => $warehouses, 
            'categories' => $categories,
            'taxRates' => $taxRates]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        $validatedData = $request->validated();
        $validatedData['created_by_user_id'] = Auth::id();
        // Ensure tenant_id is set
        if (empty($validatedData['tenant_id'])) {
            $validatedData['tenant_id'] = Auth::user()->tenant_id ?? config('tenant_id');
        }

        // If it's a service, quantity_on_hand might not be relevant or could be set to a high number/null
        if ($validatedData['is_service']) {
            // $validatedData['quantity_on_hand'] = 0; // Or handle as per your business logic for services
        }
        
        $product = Product::create($validatedData);
        $this->syncFeatures($product, $validatedData['features'] ?? []);

        if (!$product->is_service) {
            $this->syncInventory($product, $validatedData['inventory'] ?? []);
        }
        
        return redirect()->route('products.index')
                         ->with('success', 'Product/Service created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        $product->load(['createdBy', 'features', 'warehouses', 'category']); // Eager load features, warehouses and category
        return view('products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        $productFeatures = ProductFeature::orderBy('name')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        $categories = ProductCategory::orderBy('name')->get();
        $taxRates = TaxRate::where('is_active', true)->orderBy('rate')->get();
        $product->load(['features', 'warehouses', 'category']); // Load existing features, inventory and category for the form
        return view('products.edit', compact('product', 'productFeatures', 'warehouses', 'categories', 'taxRates'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $validatedData = $request->validated();
        // Ensure tenant_id persists
        if (empty($validatedData['tenant_id'])) {
            $validatedData['tenant_id'] = $product->tenant_id ?? (Auth::user()->tenant_id ?? config('tenant_id'));
        }
        $product->update($validatedData); 
        $this->syncFeatures($product, $validatedData['features'] ?? []);
        if (!$product->is_service) {
            $this->syncInventory($product, $validatedData['inventory'] ?? []);
        } else {
            $product->warehouses()->detach(); // Remove all inventory if it's changed to a service
        }
        return redirect()->route('products.index')->with('success', 'Product/Service updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        // Add checks here if product is linked to orders, invoices, etc.
        $product->delete(); // Soft delete
        return redirect()->route('products.index')->with('success', 'Product/Service deleted successfully.');
    }

    /**
     * Sync product features.
     */
    protected function syncFeatures(Product $product, array $featuresData)
    {
        $syncData = [];
        foreach ($featuresData as $feature) {
            if (!empty($feature['feature_id']) && !empty($feature['value'])) {
                // Ensure feature_id is numeric if it comes from form as string
                $syncData[ (int) $feature['feature_id']] = ['value' => $feature['value']];
            }
        }
        $product->features()->sync($syncData);
    }

    /**
     * Sync product inventory across warehouses.
     */
    protected function syncInventory(Product $product, array $inventoryData)
    {
        $syncData = [];
        foreach ($inventoryData as $warehouseId => $data) {
            if (isset($data['quantity']) && is_numeric($data['quantity'])) {
                $syncData[$warehouseId] = ['quantity' => (int)$data['quantity']];
            }
        }
        $product->warehouses()->sync($syncData);
    }
}