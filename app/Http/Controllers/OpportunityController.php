<?php

namespace App\Http\Controllers;


use App\Http\Requests\StoreOpportunityRequest;
use App\Http\Requests\UpdateOpportunityRequest;
use App\Models\Contact;
use App\Models\CrmUser;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Opportunity; // Ensure this is imported
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class OpportunityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $opportunities = Opportunity::with(['customer', 'contact', 'assignedTo'])->latest()->paginate(15);
        return view('opportunities.index', compact('opportunities'));
    }

    public function create(Request $request)
    {
        $opportunity = new Opportunity();
        $customers = Customer::orderBy('company_name')->get();
        $crmUsers = CrmUser::orderBy('full_name')->get();
        $stages = Opportunity::$stages;

        // Pre-select customer if provided (e.g., from a customer's detail page)
        $selectedCustomerId = $request->get('customer_id');
        $contacts = collect(); // Will be populated via AJAX/JS

        return view('opportunities.create', compact('opportunity', 'customers', 'crmUsers', 'stages', 'selectedCustomerId', 'contacts'));
    }

    public function store(StoreOpportunityRequest $request)
    {
        $validatedData = $request->validated();
        $validatedData['created_by_user_id'] = Auth::id();
        // Ensure tenant is set on the opportunity
        if (empty($validatedData['tenant_id'])) {
            $validatedData['tenant_id'] = Auth::user()->tenant_id ?? config('tenant_id');
        }

        $opportunity = Opportunity::create($validatedData);

        return redirect()->route('opportunities.show', $opportunity->opportunity_id)
                         ->with('success', 'Opportunity created successfully.');
    }

    public function show(Opportunity $opportunity)
    {
        $opportunity->load(['customer', 'contact', 'assignedTo', 'createdBy', 'lead']);
        return view('opportunities.show', compact('opportunity'));
    }

    public function edit(Opportunity $opportunity)
    {
        $customers = Customer::orderBy('company_name')->get();
        $crmUsers = CrmUser::orderBy('full_name')->get();
        $stages = Opportunity::$stages;

        $selectedCustomerId = $opportunity->customer_id;
        // Load contacts for the pre-selected customer for the edit form
        $contacts = Contact::where('contactable_type', Customer::class)
                           ->where('contactable_id', $selectedCustomerId)
                           ->orderBy('first_name')
                           ->get();

        return view('opportunities.edit', compact('opportunity', 'customers', 'crmUsers', 'stages', 'selectedCustomerId', 'contacts'));
    }

    public function update(UpdateOpportunityRequest $request, Opportunity $opportunity)
    {
        $opportunity->update($request->validated());

        return redirect()->route('opportunities.show', $opportunity->opportunity_id)
                         ->with('success', 'Opportunity updated successfully.');
    }

    public function destroy(Opportunity $opportunity)
    {
        $opportunity->delete(); // Soft delete

        return redirect()->route('opportunities.index')
                         ->with('success', 'Opportunity deleted successfully.');
    }

    /**
     * API endpoint to get contacts for a given customer.
     * Used for dynamic dropdowns.
     */
    public function getContactsByCustomer(Customer $customer)
    {
        // Ensure only necessary data is returned
        return response()->json(
            $customer->contacts()
                     ->select('contact_id', 'first_name', 'last_name')
                     ->get()
                     ->map(function ($contact) {
                         return [
                             'id' => $contact->contact_id,
                             'name' => $contact->first_name . ' ' . $contact->last_name,
                         ];
                     })
        );
    }
     /**
     * Display the opportunities in a Kanban board view.
     */
    public function kanban(Request $request)
    {
        $query = Opportunity::with(['customer', 'assignedTo']);

        // Apply filter for assigned user if provided
        if ($request->filled('assigned_to_user_id')) {
            $query->where('assigned_to_user_id', $request->input('assigned_to_user_id'));
        }

        $opportunitiesByStage = $query->orderBy('expected_close_date', 'asc')->get()->groupBy('stage');

        // Ensure all stages from the model are present in the final array
        $stages = Opportunity::$stages;
        $kanbanData = [];
        $stageTotals = [];
        $crmUsers = CrmUser::orderBy('full_name')->get();

        foreach ($stages as $stageKey => $stageName) {
            $opportunitiesInStage = $opportunitiesByStage->get($stageKey, collect());
            $kanbanData[$stageKey] = $opportunitiesInStage;
            $stageTotals[$stageKey] = $opportunitiesInStage->sum('amount');
        }

        return view('opportunities.kanban', compact('kanbanData', 'stages', 'stageTotals', 'crmUsers'));
    }

    /**
     * Update the stage of an opportunity via AJAX.
     */
    public function updateStage(Request $request, Opportunity $opportunity)
    {
        $validated = $request->validate([
            'stage' => ['required', 'string', Rule::in(array_keys(Opportunity::$stages))],
        ]);

        $oldStage = $opportunity->stage;
        $newStage = $validated['stage'];

        $opportunity->update(['stage' => $newStage]);

        // Stage Automation: If moved to 'Proposal', create a follow-up task.
        if ($newStage !== $oldStage && $newStage === 'Proposal') {
            $opportunity->tasks()->create(['title' => 'Prepare and send proposal for ' . $opportunity->name, 'priority' => 'High', 'due_date' => now()->addDays(3), 'assigned_to_user_id' => $opportunity->assigned_to_user_id, 'created_by_user_id' => Auth::id(), ]);
        }

        return response()->json(['success' => true, 'message' => 'Stage updated.']);
    }
}