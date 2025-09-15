<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\Tenant;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $isSuper = $user && (bool) ($user->is_super_admin ?? false);
        $requestedTenantId = $request->input('tenant_id');

        if ($isSuper) {
            $query = Supplier::withoutGlobalScopes()->latest();
            if ($requestedTenantId) {
                $query->where('tenant_id', $requestedTenantId);
            }
        } else {
            $query = Supplier::query()->latest();
        }

        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('contact_person', 'like', "%{$searchTerm}%")
                  ->orWhere('email', 'like', "%{$searchTerm}%");
            });
        }
        $suppliers = $query->paginate(10)->withQueryString();
        if ($isSuper) {
            $tenants = Tenant::orderBy('name')->get(['id','name']);
            return view('suppliers.index', compact('suppliers', 'tenants', 'requestedTenantId'));
        }
        return view('suppliers.index', compact('suppliers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('suppliers.create', ['supplier' => new Supplier()]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSupplierRequest $request)
    {
        $validatedData = $request->validated();
        
        DB::transaction(function () use ($validatedData) {
            $supplierData = collect($validatedData)->except(['addresses'])->all();
            $supplier = Supplier::create($supplierData);
            // Use validated 'addresses' if present, otherwise default to an empty array.
            $this->syncAddresses($supplier, $validatedData['addresses'] ?? []);
        });

        return redirect()->route('suppliers.index')
                         ->with('success', 'Supplier created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Supplier $supplier)
    {
        $supplier->load('addresses');
        $supplier_contacts =  $supplier->contacts->where('contactable_type', 'App\Models\Supplier')->where('contactable_id', $supplier->supplier_id);
        return view('suppliers.show', compact('supplier', 'supplier_contacts'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Supplier $supplier)
    {
        $supplier->load('addresses');
        return view('suppliers.edit', compact('supplier'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSupplierRequest $request, Supplier $supplier)
    {
        $validatedData = $request->validated();

        DB::transaction(function () use ($validatedData, $supplier) {
            $supplierData = collect($validatedData)->except(['addresses'])->all();
            $supplier->update($supplierData);
            // Use validated 'addresses' if present, otherwise default to an empty array.
            $this->syncAddresses($supplier, $validatedData['addresses'] ?? []);
        });

        return redirect()->route('suppliers.index')
                         ->with('success', 'Supplier updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier)
    {
        // Add checks if supplier is linked to products, purchase orders etc.
        $supplier->addresses()->delete(); // Delete related polymorphic addresses
        $supplier->delete();
        return redirect()->route('suppliers.index')
                         ->with('success', 'Supplier deleted successfully.');
    }

    protected function syncAddresses(Supplier $supplier, array $addressesData)
    {
        if (!empty($addressesData)) {
            $addressInput = $addressesData[0] ?? null; 
            if ($addressInput && !empty($addressInput['street_address_line_1'])) {
                if (!empty($addressInput['is_primary'])) {
                    $supplier->addresses()->where('is_primary', true)->update(['is_primary' => false]);
                }
                $supplier->addresses()->updateOrCreate(
                    ['address_id' => $addressInput['address_id'] ?? null],
                    $addressInput
                );
            } elseif (isset($addressInput['address_id']) && empty($addressInput['street_address_line_1'])) {
                // If an existing address ID is provided but street line 1 is empty, consider deleting it
                $supplier->addresses()->where('address_id', $addressInput['address_id'])->delete();
            }
        } else {
            // If no address data is submitted, potentially clear all addresses (optional, based on requirements)
            // $supplier->addresses()->delete(); 
        }
    }
}