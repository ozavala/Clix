<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Address; // Add this
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request; // Corrected import
use Illuminate\Support\Facades\Auth;


class CustomerController extends Controller
{
    
    protected $customerStatuses = [
        'Active' => 'Active',
        'Inactive' => 'Inactive',
        'Lead' => 'Lead',
        'Prospect' => 'Prospect',
    ];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
       Gate::authorize('view-customers');
       $query = Customer::with('createdBy')->latest();

        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('first_name', 'like', "%{$searchTerm}%")
                  ->orWhere('last_name', 'like', "%{$searchTerm}%")
                  ->orWhere('email', 'like', "%{$searchTerm}%")
                  ->orWhere('company_name', 'like', "%{$searchTerm}%");
            });
        } // Consider adding status to search: ->orWhere('status', 'like', "%{$searchTerm}%")
        $customers = $query->paginate(10)->withQueryString(); // withQueryString to keep search params on pagination
        return view('customers.index', compact('customers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create-customers');
        $statuses = $this->customerStatuses;
        return view('customers.create', compact('statuses'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCustomerRequest $request)
    {
        Gate::authorize('create-customers');
        $validatedData = $request->validated();
        $validatedData['created_by_user_id'] = Auth::id();
        
        $customerData = collect($validatedData)->except(['addresses'])->all();
        $customer = Customer::create($customerData);

        $this->syncAddresses($customer, $validatedData['addresses'] ?? []);
        
        return redirect()->route('customers.index')
                         ->with('success', 'Customer created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer)
    {
        Gate::authorize('view-customers');
        // Eager load all necessary relationships for the detail view
        $customer->load(['createdBy', 'addresses', 'contacts', 'notes', 'invoices']);
        // The getAllPayments method was added in a previous step to fetch payments from orders and invoices
        $payments = method_exists($customer, 'getAllPayments') ? $customer->getAllPayments() : collect();
        $invoices = $customer->invoices;
        $contacts=$customer->contacts;
        return view('customers.show', compact('customer', 'contacts','payments', 'invoices'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer)
    {
        Gate::authorize('edit-customers');
        $statuses = $this->customerStatuses;
        $customer->load('addresses'); // Load addresses for the form
        return view('customers.edit', compact('customer', 'statuses'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        Gate::authorize('edit-customers');
        $validatedData = $request->validated();
        $customerData = collect($validatedData)->except(['addresses'])->all();
        $customer->update($customerData);

        $this->syncAddresses($customer, $validatedData['addresses'] ?? []);
        return redirect()->route('customers.index')
                         ->with('success', 'Customer updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        Gate::authorize('delete-customers');
        // Add checks here if customer is linked to orders, invoices, etc.
        // For example:
        // if ($customer->orders()->exists()) {
        //     return redirect()->route('customers.index')
        //                      ->with('error', 'Cannot delete customer. They have existing orders.');
        // }

        try {
            $customer->delete();
            return redirect()->route('customers.index')
                             ->with('success', 'Customer deleted successfully.');
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle potential foreign key constraint violations if not handled by DB or model
            return redirect()->route('customers.index')
                             ->with('error', 'Could not delete customer. They might be associated with other records.');
        }
    }

    /**
     * Sync customer addresses.
     * For now, this handles a single address block from the form, intended as primary.
     * This method creates, updates, and deletes addresses based on the form submission.
     * @param Customer $customer The customer model.
     * @param array $addressesData The array of address data from the request.
     */
    protected function syncAddresses(Customer $customer, array $addressesData)
    {
        $submittedAddressIds = [];

        // First, handle the primary flag. Find if any submitted address is primary.
        $primaryAddressIndex = null;
        foreach ($addressesData as $index => $addressInput) {
            if (!empty($addressInput['is_primary'])) {
                $primaryAddressIndex = $index;
                break;
            }
        }

        // If a primary address is set, un-set all others for this customer first.
        if ($primaryAddressIndex !== null) {
            $customer->addresses()->update(['is_primary' => false]);
        }

        // Now, iterate and sync each address
        foreach ($addressesData as $index => $addressInput) {
            // Skip empty address blocks that might be submitted
            if (empty($addressInput['street_address_line_1'])) {
                continue;
            }
            $dataToSync = $addressInput;
            $dataToSync['is_primary'] = ($index === $primaryAddressIndex);
            $address = $customer->addresses()->updateOrCreate(['address_id' => $addressInput['address_id'] ?? null], $dataToSync);
            $submittedAddressIds[] = $address->address_id;
        }

        // Delete addresses that were removed from the form
        $customer->addresses()->whereNotIn('address_id', $submittedAddressIds)->delete();
    }
}