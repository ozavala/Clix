<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Http\Requests\StoreActivityRequest;
use App\Models\CrmUser;
use App\Models\Activity; // Add Activity model
use App\Http\Requests\StoreLeadRequest;
use App\Http\Requests\UpdateLeadRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LeadController extends TenantAwareController
{
    // Define statuses and sources for dropdowns
    protected $leadStatuses = [
        'New' => 'New',
        'Contacted' => 'Contacted',
        'Qualified' => 'Qualified',
        'Proposal Sent' => 'Proposal Sent',
        'Negotiation' => 'Negotiation',
        'Won' => 'Won',
        'Lost' => 'Lost',
        'On Hold' => 'On Hold',
    ];

    protected $leadSources = [
        'Website' => 'Website',
        'Referral' => 'Referral',
        'Cold Call' => 'Cold Call',
        'Advertisement' => 'Advertisement',
        'Event' => 'Event',
        'Other' => 'Other',
    ];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Lead::with(['customer', 'assignedTo', 'createdBy'])->latest();

        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                  ->orWhere('contact_name', 'like', "%{$searchTerm}%")
                  ->orWhere('contact_email', 'like', "%{$searchTerm}%")
                  ->orWhereHas('customer', function ($cq) use ($searchTerm) {
                      $cq->where('first_name', 'like', "%{$searchTerm}%")
                         ->orWhere('last_name', 'like', "%{$searchTerm}%")
                         ->orWhere('company_name', 'like', "%{$searchTerm}%");
                  });
            });
        }

        if ($request->filled('status_filter')) {
            $query->where('status', $request->input('status_filter'));
        }

        if ($request->filled('source_filter')) {
            $query->where('source', $request->input('source_filter'));
        }

        if ($request->filled('assigned_to_filter')) {
            $query->where('assigned_to_user_id', $request->input('assigned_to_filter'));
        }

        $leads = $query->paginate(10)->withQueryString();
        
        // Data for filter dropdowns
        $filterStatuses = $this->leadStatuses;
        $filterSources = $this->leadSources;
        $crmUsers = CrmUser::orderBy('full_name')->pluck('full_name', 'user_id');

        return view('leads.index', compact('leads', 'filterStatuses', 'filterSources', 'crmUsers'));
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $statuses = $this->leadStatuses;
        $sources = $this->leadSources;
        $customers = Customer::orderBy('first_name')->orderBy('last_name')->get();
        $crmUsers = CrmUser::orderBy('full_name')->get();
        return view('leads.create', compact('statuses', 'sources', 'customers', 'crmUsers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLeadRequest $request)
    {
        $validatedData = $request->validated();
        $validatedData['created_by_user_id'] = Auth::id();
        // Ensure tenant_id is set for the lead
        if (empty($validatedData['tenant_id'])) {
            $validatedData['tenant_id'] = Auth::user()->tenant_id ?? config('tenant_id');
        }

        Lead::create($validatedData);

        return redirect()->route('leads.index')
                         ->with('success', 'Lead created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Lead $lead)
    {
        $lead->load(['customer', 'assignedTo', 'createdBy', 'activities.user']); // Eager load activities and their users
        return view('leads.show', compact('lead')); // Activities will be loaded via relationship in the view
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Lead $lead)
    {
        $statuses = $this->leadStatuses;
        $sources = $this->leadSources;
        $customers = Customer::orderBy('first_name')->orderBy('last_name')->get();
        $crmUsers = CrmUser::orderBy('full_name')->get();
        return view('leads.edit', compact('lead', 'statuses', 'sources', 'customers', 'crmUsers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLeadRequest $request, Lead $lead)
    {
        $lead->update($request->validated());
        return redirect()->route('leads.index')->with('success', 'Lead updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Lead $lead)
    {
        // Add checks here if lead is linked to other critical data before deletion
        $lead->delete(); // This will soft delete due to the trait in the model
        return redirect()->route('leads.index')->with('success', 'Lead deleted successfully.');
    }

    /**
     * Store a new activity for the specified lead.
     */
    /**
     * Store a newly created activity for the lead.
     */
    public function storeActivity(StoreActivityRequest $request, Lead $lead)
    {
        $validatedData = $request->validated();

        $activity = new Activity($validatedData);
        $activity->user_id = Auth::id(); // User who performed/logged the activity
        // lead_id is automatically set by the relationship

        $lead->activities()->save($activity);

        return redirect()->route('leads.show', $lead->lead_id)
                         ->with('success', 'Activity added successfully.');
    }
    /**
     * Convert the specified lead to a customer.
     */
    public function convertToCustomer(Request $request, Lead $lead)
    {
        if (in_array($lead->status, ['Won', 'Lost'])) {
            return redirect()->route('leads.show', $lead)->with('error', 'Lead is already Won or Lost and cannot be converted again.');
        }

        return DB::transaction(function () use ($lead) {
            $customer = null;
            if ($lead->customer_id) {
                $customer = Customer::find($lead->customer_id);
                if (!$customer) {
                    // This is an unlikely scenario, but good to handle.
                    // We'll proceed to create a new customer profile.
                }
            }

            if (!$customer) {
                if (empty($lead->contact_name)) {
                    return redirect()->route('leads.show', $lead)->with('error', 'Lead contact name is missing. Cannot create customer.');
                }
                $nameParts = explode(' ', $lead->contact_name, 2);
                $firstName = $nameParts[0];
                $lastName = $nameParts[1] ?? '';

                // Create or find customer based on email to avoid duplicates
                $customer = Customer::firstOrCreate(
                    ['email' => $lead->contact_email],
                    [
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'phone_number' => $lead->contact_phone,
                        'company_name' => $lead->title, // Use lead title as a guess for company name
                        'status' => 'Active',
                        'type' => 'Company', // Default to company, adjust if needed
                        'legal_id' => 'TEMP-' . uniqid(), // Placeholder legal ID to satisfy validation
                        'created_by_user_id' => Auth::id(),
                    ]
                );
            }

            // Create an opportunity
            $opportunity = Opportunity::create([
                'name' => $lead->title,
                'customer_id' => $customer->customer_id,
                'lead_id' => $lead->lead_id,
                'stage' => array_key_first(Opportunity::$stages), // e.g., 'Qualification'
                'amount' => $lead->value,
                'expected_close_date' => $lead->expected_close_date,
                'assigned_to_user_id' => $lead->assigned_to_user_id ?? Auth::id(),
                'created_by_user_id' => Auth::id(),
            ]);

            // Update lead status and link to the new customer
            $lead->update(['status' => 'Won', 'customer_id' => $customer->customer_id]);

            return redirect()->route('opportunities.show', $opportunity->opportunity_id)
                             ->with('success', 'Lead successfully converted to an opportunity.');
        });
    }
}