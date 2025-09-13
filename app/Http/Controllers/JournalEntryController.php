<?php

namespace App\Http\Controllers;

use App\Models\JournalEntry;
use App\Models\Account;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JournalEntryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = JournalEntry::with(['lines', 'referenceable', 'createdBy'])->latest('entry_date')->latest('journal_entry_id');

        // Tenant scoping: normal users see own tenant; super admin aggregates unless tenant_id provided
        $user = Auth::user();
        $isSuper = $user && (bool) ($user->is_super_admin ?? false);
        $requestedTenantId = $request->input('tenant_id');
        $currentTenantId = config('tenant_id') ?: ($user?->tenant_id);
        if (!$isSuper) {
            $query->where('tenant_id', $currentTenantId);
        } elseif ($requestedTenantId) {
            $query->where('tenant_id', $requestedTenantId);
        }

        if ($request->filled('search_description')) {
            $query->where('description', 'like', '%' . $request->input('search_description') . '%');
        }
        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->input('transaction_type'));
        }

        $journalEntries = $query->paginate(20)->withQueryString();
        // You might want to get distinct transaction types for a filter dropdown
        $transactionTypes = JournalEntry::select('transaction_type')->distinct()->pluck('transaction_type');

        return view('journal_entries.index', compact('journalEntries', 'transactionTypes'));
    }

    /**
     * Display the specified resource.
     */
    public function show(JournalEntry $journalEntry)
    {
        $journalEntry->load(['lines.entity', 'referenceable', 'createdBy']);
        return view('journal_entries.show', compact('journalEntry'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // For manual entries, we might not pre-populate much beyond a default date
        $journalEntry = new JournalEntry(['entry_date' => now()->format('Y-m-d')]);
        // Define some common transaction types for manual entries
        $manualTransactionTypes = ['Manual Journal', 'Adjustment', 'Opening Balance'];
        return view('journal_entries.create', compact('journalEntry', 'manualTransactionTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'entry_date' => 'required|date',
            'transaction_type' => 'required|string|max:255',
            'description' => 'nullable|string',
            'lines' => 'required|array|min:2', // Must have at least two lines for a balanced entry
            'lines.*.account_code' => 'required|string|max:255',
            'lines.*.debit_amount' => 'nullable|numeric|min:0|required_without:lines.*.credit_amount',
            'lines.*.credit_amount' => 'nullable|numeric|min:0|required_without:lines.*.debit_amount',
            // Add validation for entity_id and entity_type if you implement them in the form
        ]);

        // Enforce that all accounts belong to the current tenant
        $tenantId = config('tenant_id') ?: (Auth::user()?->tenant_id);
        foreach ($validatedData['lines'] as $idx => $line) {
            $exists = Account::where('code', $line['account_code'])
                ->where('tenant_id', $tenantId)
                ->exists();
            if (!$exists) {
                return back()->withInput()->withErrors(["lines.$idx.account_code" => 'Selected account does not belong to your company.']);
            }
        }

        // Basic check for balanced entry (sum of debits == sum of credits)
        $totalDebits = collect($validatedData['lines'])->sum('debit_amount');
        $totalCredits = collect($validatedData['lines'])->sum('credit_amount');

        if (round($totalDebits, 2) !== round($totalCredits, 2)) {
            return back()->withInput()->withErrors(['lines' => 'Journal entry must be balanced (total debits must equal total credits).']);
        }
        if ($totalDebits == 0 && $totalCredits == 0) {
             return back()->withInput()->withErrors(['lines' => 'Journal entry amounts cannot both be zero.']);
        }


        DB::transaction(function () use ($validatedData, $tenantId) {
            $journalEntry = JournalEntry::create([
                'tenant_id' => $tenantId,
                'entry_date' => $validatedData['entry_date'],
                'transaction_type' => $validatedData['transaction_type'],
                'description' => $validatedData['description'],
                'created_by_user_id' => Auth::id(),
                // 'referenceable_id' and 'referenceable_type' will be null for manual entries
            ]);

            foreach ($validatedData['lines'] as $lineData) {
                $journalEntry->lines()->create([
                    'tenant_id' => $tenantId,
                    'account_code' => $lineData['account_code'],
                    'debit_amount' => $lineData['debit_amount'] ?? 0,
                    'credit_amount' => $lineData['credit_amount'] ?? 0,
                    // 'entity_id' => $lineData['entity_id'] ?? null,
                    // 'entity_type' => $lineData['entity_type'] ?? null,
                ]);
            }
        });

        return redirect()->route('journal-entries.index')->with('success', 'Journal Entry created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     * Note: Editing complex journal entries (especially those auto-generated) can be tricky.
     * This example assumes editing manually created ones.
     */
    public function edit(JournalEntry $journalEntry)
    {
        $journalEntry->load('lines');
        // For simplicity, we'll allow editing basic fields.
        // More complex logic might be needed if it's linked to other records.
        if ($journalEntry->referenceable_id) {
            return redirect()->route('journal-entries.show', $journalEntry)
                             ->with('error', 'Automatically generated journal entries cannot be edited directly.');
        }
        $manualTransactionTypes = ['Manual Journal', 'Adjustment', 'Opening Balance']; // Same as create
        return view('journal_entries.edit', compact('journalEntry', 'manualTransactionTypes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, JournalEntry $journalEntry)
    {
        // Similar validation and logic as store(), but also handle updating/deleting/adding lines.
        // This is a simplified update. A full implementation would need careful handling of line item changes.
        // For now, we'll redirect as this is complex.
        // Consider making journal entries immutable or only allowing reversal entries.
        return redirect()->route('journal-entries.show', $journalEntry)->with('info', 'Updating journal entries is a complex operation and is not fully implemented in this example. Consider creating a reversing entry instead.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(JournalEntry $journalEntry)
    {
        // Add checks: e.g., prevent deletion if it's linked to a payment that still exists.
        // Or, if it's part of a closed accounting period.
        if ($journalEntry->referenceable_id) { // Prevent deleting auto-generated entries easily
            return redirect()->route('journal-entries.index')->with('error', 'Cannot delete automatically generated journal entries. Reverse the original transaction instead.');
        }
        DB::transaction(function () use ($journalEntry) {
            $journalEntry->lines()->delete();
            $journalEntry->delete();
        });
        return redirect()->route('journal-entries.index')->with('success', 'Journal Entry deleted successfully.');
    }
}