<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Models\Contact;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Support\Facades\Auth; // Keep this
use Illuminate\Support\Facades\Route;


class ContactController extends TenantAwareController
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) : mixed {
            if ($request->has('contact') && ($contact = $request->route('contact')) instanceof Contact) {
                if ($contact && !$contact->relationLoaded('contactable')) {
                    $contact->load('contactable');
                }
            }
            return $next($request);
        })->only(['show', 'edit', 'update', 'destroy']);
    }

    /**
     * Display a listing of the resource.
     */
   public function index()
    {
        $contacts = Contact::with('contactable')->latest()->paginate(15);
        return view('contacts.index', compact('contacts'));
    }

    public function create(Request $request)
    {
        $contact = new Contact();

        // Check for pre-selection from a parent entity's page, e.g., from a "Add Contact" button on a customer's page.
        // This allows passing ?customer_id=123 in the URL.
        if ($request->filled('customer_id')) {
            $contact->contactable_type = Customer::class;
            $contact->contactable_id = $request->input('customer_id');
        } elseif ($request->filled('supplier_id')) {
            $contact->contactable_type = Supplier::class;
            $contact->contactable_id = $request->input('supplier_id');
        }

        $customers = Customer::orderBy('company_name')->get();
        $suppliers = Supplier::orderBy('name')->get();
        return view('contacts.create', compact('contact', 'customers', 'suppliers'));
    }

    public function store(StoreContactRequest $request)
    {
        $validatedData = $request->validated();
        $validatedData['created_by_user_id'] = Auth::id(); // Use Auth::id() for consistency
        // Ensure tenant_id is set from context or derived from selected entity
        if (empty($validatedData['tenant_id'])) {
            $validatedData['tenant_id'] = $this->getTenantId();
        }
        $contact = Contact::create($validatedData);
        return $this->redirectToContactableShow($contact->contactable_type, $contact->contactable_id, 'Contact created successfully.');
    }
        
    

    /**
     * Display the specified contact with its related contactable entity.
     *
     * @param  \App\Models\Contact  $contact
     * @return \Illuminate\View\View
     */
    public function show(Contact $contact)
    {
        // The 'contactable' relation is already eager loaded in the constructor middleware
        return view('contacts.show', compact('contact'));
    }

    public function edit(Contact $contact)
    {
        // Order by company_name for consistency and better display in the dropdown
        $customers = Customer::orderBy('company_name')->get();
        $suppliers = Supplier::orderBy('name')->get();
        return view('contacts.edit', compact('contact', 'customers', 'suppliers'));
    }

    public function update(UpdateContactRequest $request, Contact $contact)
    {
       $contact->update($request->validated());        
        return $this->redirectToContactableShow($contact->contactable_type, $contact->contactable_id, 'Contact updated successfully.');
       
    }

    public function destroy(Contact $contact)
    {
        $contactableId = $contact->contactable_id;
        $contactableType = $contact->contactable_type;

        $contact->delete(); // Soft delete

        // Use the helper method for consistent redirection after deletion
        return $this->redirectToContactableShow($contactableType, $contactableId, 'Contact deleted successfully.');
    }
    /**
     * Helper to redirect to the contactable entity's show page.
     */
    private function redirectToContactableShow(string $contactableType, ?int $contactableId, string $message)
    {
        if ($contactableId && $contactableType === \App\Models\Customer::class) {
            return redirect()->route('customers.show', $contactableId)->with('success', $message);
        } elseif ($contactableId && $contactableType === \App\Models\Supplier::class) {
            return redirect()->route('suppliers.show', $contactableId)->with('success', $message);
        }
        return redirect()->route('contacts.index')->with('success', $message);
    }

}
