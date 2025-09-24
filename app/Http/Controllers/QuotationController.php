<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use App\Models\Opportunity;
use App\Models\Product;
use App\Http\Requests\StoreQuotationRequest;
use App\Http\Requests\UpdateQuotationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\QuotationEmail;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;

class QuotationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Quotation::with(['opportunity', 'opportunity.customer'])->latest();

        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('subject', 'like', "%{$searchTerm}%")
                  ->orWhereHas('opportunity', fn($oq) => $oq->where('name', 'like', "%{$searchTerm}%"))
                  ->orWhereHas('opportunity.customer', fn($cq) => 
                        $cq->where('first_name', 'like', "%{$searchTerm}%")
                           ->orWhere('last_name', 'like', "%{$searchTerm}%")
                    );
            });
        }
        if ($request->filled('status_filter')) {
            $query->where('status', $request->input('status_filter'));
        }

        $quotations = $query->paginate(10)->withQueryString();
        $statuses = Quotation::$statuses;
        return view('quotations.index', compact('quotations', 'statuses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $statuses = Quotation::$statuses;
        $opportunities = Opportunity::orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();
        
        $selectedOpportunity = null;
        if ($request->filled('opportunity_id')) {
            // Eager load the customer to prevent extra queries in the view
            $selectedOpportunity = Opportunity::with('customer')->find($request->input('opportunity_id'));
        }

        return view('quotations.create', compact('statuses', 'opportunities', 'products', 'selectedOpportunity'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreQuotationRequest $request)
    {
        $validatedData = $request->validated();
        
        // Resolve tenant_id preferring CRM guard tenant, then currentTenant
        $crmUser = Auth::guard('crm')->user();
        $validatedData['tenant_id'] = ($crmUser && $crmUser->tenant_id)
            ? $crmUser->tenant_id
            : ((app()->bound('currentTenant') && app('currentTenant')) ? (app('currentTenant')->id ?? app('currentTenant')->tenant_id) : null);

        // Obtener parámetros de settings
        $quotationPrefix = Setting::where('key', 'quotation_prefix')
            ->where('tenant_id', $validatedData['tenant_id'])
            ->value('value') ?? 'C-';
        $quotationStart = Setting::where('key', 'quotation_start_number')
            ->where('tenant_id', $validatedData['tenant_id'])
            ->value('value') ?? 1;
        $defaultTerms = Setting::where('key', 'default_payment_terms')
            ->where('tenant_id', $validatedData['tenant_id'])
            ->value('value') ?? 'Contado';
        $defaultDueDays = (int) (Setting::where('key', 'default_due_days')
            ->where('tenant_id', $validatedData['tenant_id'])
            ->value('value') ?? 30);

        return DB::transaction(function () use ($validatedData, $quotationPrefix, $quotationStart, $defaultTerms, $defaultDueDays) {
            $quotationData = collect($validatedData)->except(['items'])->all();
            $quotationData['created_by_user_id'] = optional(Auth::guard('crm')->user())->user_id;

            // Generar número de cotización correlativo
            $lastQuotation = Quotation::where('tenant_id', $validatedData['tenant_id'])
                ->orderByDesc('quotation_id')
                ->first();
            $nextNumber = $lastQuotation ? ($lastQuotation->quotation_id ?? $lastQuotation->id) + 1 : (int)$quotationStart;
            $quotationData['quotation_number'] = $quotationPrefix . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

            // Nota: columnas payment_terms y due_date no existen en la tabla, no las seteamos

            // Calculate totals before creating quotation
            $totals = $this->calculateTotals(
                $validatedData['items'] ?? [],
                $validatedData['discount_type'] ?? null,
                $validatedData['discount_value'] ?? 0,
                $validatedData['tax_percentage'] ?? 0
            );
            $quotationData = array_merge($quotationData, $totals);

            $quotation = Quotation::create($quotationData);

            // Create quotation items
            foreach ($validatedData['items'] ?? [] as $itemData) {
                $itemData['item_total'] = ($itemData['quantity'] ?? 0) * ($itemData['unit_price'] ?? 0);
                $quotation->items()->create($itemData);
            }

            return redirect()->route('quotations.index')
                             ->with('success', 'Quotation created successfully.');
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(Quotation $quotation)
    {
        $quotation->load(['opportunity.customer', 'createdBy', 'items.product', 'invoice']);
        return view('quotations.show', compact('quotation'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Quotation $quotation)
    {
        $statuses = Quotation::$statuses;
        $opportunities = Opportunity::orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $quotation->load('items');
        return view('quotations.edit', compact('quotation', 'statuses', 'opportunities', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateQuotationRequest $request, Quotation $quotation)
    {
        $validatedData = $request->validated();

        return DB::transaction(function () use ($validatedData, $quotation) {
            $quotationData = collect($validatedData)->except(['items'])->all();

            // Calculate totals before updating quotation
            $totals = $this->calculateTotals(
                $validatedData['items'] ?? [],
                $validatedData['discount_type'] ?? null,
                $validatedData['discount_value'] ?? 0,
                $validatedData['tax_percentage'] ?? 0
            );
            $quotationData = array_merge($quotationData, $totals);
            
            $quotation->update($quotationData);

            // Sync quotation items
            $existingItemIds = $quotation->items->pluck('quotation_item_id')->all();
            $newItemIds = [];

            foreach ($validatedData['items'] ?? [] as $itemData) {
                $itemData['item_total'] = ($itemData['quantity'] ?? 0) * ($itemData['unit_price'] ?? 0);
                if (isset($itemData['quotation_item_id']) && in_array($itemData['quotation_item_id'], $existingItemIds)) {
                    // Update existing item
                    $item = $quotation->items()->find($itemData['quotation_item_id']);
                    $item->update($itemData);
                    $newItemIds[] = $item->quotation_item_id;
                } else {
                    // Create new item
                    $newItem = $quotation->items()->create($itemData);
                    $newItemIds[] = $newItem->quotation_item_id;
                }
            }
            // Delete items that were removed from the form
            $itemsToDelete = array_diff($existingItemIds, $newItemIds);
            if (!empty($itemsToDelete)) {
                $quotation->items()->whereIn('quotation_item_id', $itemsToDelete)->delete();
            }

            return redirect()->route('quotations.index')
                             ->with('success', 'Quotation updated successfully.');
        });
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Quotation $quotation)
    {
        // Add checks if quotation is linked to invoices etc.
        $quotation->items()->delete(); // Delete related items first
        $quotation->delete();
        return redirect()->route('quotations.index')
                         ->with('success', 'Quotation deleted successfully.');
    }

    /**
     * Send the quotation email to the customer.
     */
    public function sendEmail(Quotation $quotation)
    {
        // Eager load the necessary relationships
        $quotation->load('opportunity.customer');

        // Ensure the customer and email exist
        if (!$quotation->opportunity || !$quotation->opportunity->customer || !$quotation->opportunity->customer->email) {
            return redirect()->back()->with('error', 'Customer email not found.');
        }

        // Send the email
        Mail::to($quotation->opportunity->customer->email)->send(new QuotationEmail($quotation));

        $quotation->status = 'Sent';
        $quotation->save();

        return redirect()->route('quotations.show', $quotation)
                         ->with('success', 'Quotation sent successfully.');
    }

    /**
     * Calculate subtotal, tax, discount, total for the quotation.
     * This is a basic calculation. You might need more complex logic for tax/discount.
     */
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

    /**
     * Get the items for a specific quotation as JSON.
     *
     * @param \App\Models\Quotation $quotation
     * @return \Illuminate\Http\JsonResponse
     */
    public function getItemsJson(Quotation $quotation)
    {
        return response()->json([
            'items' => $quotation->items,
            'discount_type' => $quotation->discount_type,
            'discount_value' => $quotation->discount_value,
            'tax_percentage' => $quotation->tax_percentage,
        ]);
    }

    /**
     * Generar y descargar PDF de la cotización.
     */
    public function printPdf(Quotation $quotation)
    {
        // Obtener logo si existe
        $logoPath = config('settings.company_logo');
        $logoData = null;
        if ($logoPath && \Storage::disk('public')->exists($logoPath)) {
            $logoData = 'data:image/png;base64,' . base64_encode(\Storage::disk('public')->get($logoPath));
        }
        $quotation->load(['opportunity.customer', 'items']);
        $pdf = Pdf::loadView('quotations.pdf', compact('quotation', 'logoData'));
        return $pdf->download('Cotizacion_' . $quotation->quotation_number . '.pdf');
    }
}
