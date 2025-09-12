<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Quotation;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\InvoiceReminder;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Setting;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Invoice::with(['order', 'customer'])->latest();

        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('invoice_number', 'like', "%{$searchTerm}%")
                  ->orWhereHas('customer', fn($cq) =>
                        $cq->where('first_name', 'like', "%{$searchTerm}%")
                           ->orWhere('last_name', 'like', "%{$searchTerm}%")
                    )
                  ->orWhereHas('order', fn($oq) => $oq->where('order_number', 'like', "%{$searchTerm}%"));
            });
        }
        if ($request->filled('status_filter')) {
            $query->where('status', $request->input('status_filter'));
        }

        $invoices = $query->paginate(10)->withQueryString();
        $statuses = Invoice::$statuses;
        return view('invoices.index', compact('invoices', 'statuses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $statuses = Invoice::$statuses;
        // Fetch orders that are not yet fully invoiced or are in a state that allows invoicing
        // This logic might need to be more sophisticated based on your workflow
        $orders = Order::whereNotIn('status', ['Cancelled', 'Completed']) // Example filter
                       ->orderBy('order_number')
                       ->get();
        $quotations = Quotation::where('status', 'Accepted') // Only from accepted quotations
                               ->whereDoesntHave('invoice') // That don't have an invoice yet
                               ->orderBy('subject')
                               ->get();
        $customers = Customer::orderBy('first_name')->orderBy('last_name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();

        $invoice = new Invoice();
        $orderItems = [];
        $customer = null;

        if ($request->filled('clone_from')) {
            $sourceInvoice = Invoice::with('items')->find($request->input('clone_from'));
            if ($sourceInvoice) {
                $invoice->customer_id = $sourceInvoice->customer_id;
                $invoice->terms_and_conditions = $sourceInvoice->terms_and_conditions;
                $invoice->notes = $sourceInvoice->notes;
                $invoice->discount_type = $sourceInvoice->discount_type;
                $invoice->discount_value = $sourceInvoice->discount_value;
                $invoice->tax_percentage = $sourceInvoice->tax_percentage;
                
                foreach ($sourceInvoice->items as $item) {
                    $orderItems[] = [
                        'product_id' => $item->product_id,
                        'item_name' => $item->item_name,
                        'item_description' => $item->item_description,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                    ];
                }
            }
        } elseif ($request->filled('order_id')) {
            $order = Order::with('items.product', 'customer')->find($request->input('order_id'));
            if ($order) {
                $invoice->order_id = $order->order_id;
                $invoice->customer_id = $order->customer_id;
                $customer = $order->customer;
                foreach ($order->items as $orderItem) {
                    $orderItems[] = [
                        'product_id' => $orderItem->product_id,
                        'item_name' => $orderItem->item_name,
                        'item_description' => $orderItem->item_description,
                        'quantity' => $orderItem->quantity,
                        'unit_price' => $orderItem->unit_price,
                    ];
                }
                $invoice->discount_type = $order->discount_type;
                $invoice->discount_value = $order->discount_value;
                $invoice->tax_percentage = $order->tax_percentage;
            }
        } elseif ($request->filled('quotation_id')) {
            $quotation = Quotation::with('items.product', 'opportunity.customer')->find($request->input('quotation_id'));
            if ($quotation) {
                $invoice->quotation_id = $quotation->quotation_id;
                $invoice->customer_id = $quotation->opportunity->customer_id;
                foreach ($quotation->items as $quotationItem) {
                    $orderItems[] = [
                        'product_id' => $quotationItem->product_id,
                        'item_name' => $quotationItem->item_name,
                        'item_description' => $quotationItem->item_description,
                        'quantity' => $quotationItem->quantity,
                        'unit_price' => $quotationItem->unit_price,
                    ];
                }
                $invoice->discount_type = $quotation->discount_type;
                $invoice->discount_value = $quotation->discount_value;
                $invoice->tax_percentage = $quotation->tax_percentage;
            }
        }

        $order = $order ?? null;
        $invoice->invoice_number = 'INV-' . strtoupper(Str::random(8)); // Suggest an invoice number

        return view('invoices.create', compact('invoice', 'statuses', 'orders', 'quotations', 'customers', 'products', 'orderItems', 'order', 'customer'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInvoiceRequest $request)
    {
        $validatedData = $request->validated();

        // Obtener parámetros de settings
        $invoicePrefix = Setting::where('key', 'invoice_prefix')->value('value') ?? 'F-';
        $invoiceStart = Setting::where('key', 'invoice_start_number')->value('value') ?? 1;
        $defaultTerms = Setting::where('key', 'default_payment_terms')->value('value') ?? 'Contado';
        $defaultDueDays = Setting::where('key', 'default_due_days')->value('value') ?? 30;

        return DB::transaction(function () use ($validatedData, $invoicePrefix, $invoiceStart, $defaultTerms, $defaultDueDays) {
            $invoiceData = collect($validatedData)->except(['items'])->all();
            $invoiceData['created_by_user_id'] = Auth::id();
            $invoiceData['amount_paid'] = 0; // Initially no amount paid
            // Ensure tenant_id is present
            if (empty($invoiceData['tenant_id'])) {
                $invoiceData['tenant_id'] = Auth::user()->tenant_id ?? config('tenant_id');
            }

            // Generar número de factura correlativo
            $lastInvoice = Invoice::orderByDesc('id')->first();
            $nextNumber = $lastInvoice ? ($lastInvoice->number ?? $lastInvoice->id) + 1 : $invoiceStart;
            $invoiceData['number'] = $nextNumber;
            $invoiceData['invoice_number'] = $invoicePrefix . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

            // Condiciones de pago por defecto
            if (empty($invoiceData['payment_terms'])) {
                $invoiceData['payment_terms'] = $defaultTerms;
            }
            if (empty($invoiceData['due_date'])) {
                $invoiceData['due_date'] = now()->addDays($defaultDueDays);
            }

            $totals = $this->calculateTotals(
                $validatedData['items'] ?? [],
                $validatedData['discount_type'] ?? null,
                $validatedData['discount_value'] ?? 0,
                $validatedData['tax_percentage'] ?? 0
            );
            $invoiceData = array_merge($invoiceData, $totals);

            $invoice = Invoice::create($invoiceData);

            foreach ($validatedData['items'] ?? [] as $itemData) {
                $itemData['item_total'] = ($itemData['quantity'] ?? 0) * ($itemData['unit_price'] ?? 0);
                $invoice->items()->create($itemData);
            }

            // If invoice was created from a quotation, update the quotation status
            if ($invoice->quotation_id) {
                $quotation = Quotation::find($invoice->quotation_id);
                $quotation?->update(['status' => 'Invoiced']);
            }

            return redirect()->route('invoices.index')
                             ->with('success', 'Invoice created successfully.');
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(Invoice $invoice)
    {
        $invoice->load(['order', 'customer', 'createdBy', 'items', 'items.product', 'payments']);
        return view('invoices.show', compact('invoice'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Invoice $invoice)
    {
        $statuses = Invoice::$statuses;
        $orders = Order::orderBy('order_number')->get();
        $quotations = Quotation::orderBy('subject')->get();
        $customers = Customer::orderBy('first_name')->orderBy('last_name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();
        
        $invoice->load('items');
        $orderItems = $invoice->items->toArray(); // For the form structure

        return view('invoices.edit', compact('invoice', 'statuses', 'orders', 'quotations', 'customers', 'products', 'orderItems'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice)
    {
        $validatedData = $request->validated();

        return DB::transaction(function () use ($validatedData, $invoice) {
            $invoiceData = collect($validatedData)->except(['items'])->all();

            $totals = $this->calculateTotals(
                $validatedData['items'] ?? [],
                $validatedData['discount_type'] ?? null,
                $validatedData['discount_value'] ?? 0,
                $validatedData['tax_percentage'] ?? 0
            );
            $invoiceData = array_merge($invoiceData, $totals);

            $invoice->update($invoiceData);

            $existingItemIds = $invoice->items->pluck('invoice_item_id')->all();
            $newItemIds = [];

            foreach ($validatedData['items'] ?? [] as $itemData) {
                $itemData['item_total'] = ($itemData['quantity'] ?? 0) * ($itemData['unit_price'] ?? 0);
                if (isset($itemData['invoice_item_id']) && in_array($itemData['invoice_item_id'], $existingItemIds)) {
                    $item = $invoice->items()->find($itemData['invoice_item_id']);
                    $item->update($itemData);
                    $newItemIds[] = $item->invoice_item_id;
                } else {
                    $newItem = $invoice->items()->create($itemData);
                    $newItemIds[] = $newItem->invoice_item_id;
                }
            }
            $itemsToDelete = array_diff($existingItemIds, $newItemIds);
            if (!empty($itemsToDelete)) {
                $invoice->items()->whereIn('invoice_item_id', $itemsToDelete)->delete();
            }

            // Recalculate invoice status if total amount changed and it affects paid status
            if ($invoice->amount_paid >= $invoice->total_amount && $invoice->total_amount > 0) {
                $invoice->status = 'Paid';
            } elseif ($invoice->amount_paid > 0 && $invoice->amount_paid < $invoice->total_amount) {
                $invoice->status = 'Partially Paid';
            } elseif ($invoice->amount_paid <= 0 && $invoice->status === 'Paid') { // If total reduced below paid amount
                $invoice->status = 'Sent'; // Or other appropriate status
            }
            $invoice->save();


            return redirect()->route('invoices.index')
                             ->with('success', 'Invoice updated successfully.');
        });
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Invoice $invoice)
    {
        if ($invoice->payments()->exists()) {
            return redirect()->route('invoices.index')
                             ->with('error', 'Cannot delete invoice with existing payments. Please delete payments first.');
        }
        $invoice->items()->delete();
        $invoice->delete();
        return redirect()->route('invoices.index')
                         ->with('success', 'Invoice deleted successfully.');
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

    /**
     * Generate a PDF for the specified invoice.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function printPdf(Invoice $invoice)
    {
        $invoice->load(['customer.addresses', 'items.product']);

        $logoPath = config('settings.company_logo');
        $logoData = null;
        if ($logoPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($logoPath)) {
            $path = \Illuminate\Support\Facades\Storage::disk('public')->path($logoPath);
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);
            $logoData = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        $pdf = Pdf::loadView('invoices.pdf', compact('invoice', 'logoData'));
        
        return $pdf->stream('invoice_' . $invoice->invoice_number . '.pdf');
    }

    /**
     * Get the items for a specific order as JSON.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOrderItemsJson(Order $order)
    {
        return response()->json([
            'items' => $order->items,
            'discount_type' => $order->discount_type,
            'discount_value' => $order->discount_value,
            'tax_percentage' => $order->tax_percentage,
        ]);
    }

    /**
     * Send a payment reminder for the specified invoice.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendReminder(Invoice $invoice)
    {
        if ($invoice->status !== 'Overdue' && $invoice->amount_due <= 0) {
            return redirect()->back()->with('error', 'A reminder can only be sent for overdue invoices with an amount due.');
        }

        if (!$invoice->customer || !$invoice->customer->email) {
            return redirect()->back()->with('error', 'Customer email not found. Cannot send reminder.');
        }

        Mail::to($invoice->customer->email)->send(new InvoiceReminder($invoice));

        return redirect()->route('invoices.show', $invoice)
                         ->with('success', 'Payment reminder sent successfully.');
    }

    /**
     * Send invoice email.
     */
    public function send(Invoice $invoice)
    {
        // Update invoice status to sent
        $invoice->update(['status' => 'Sent']);
        
        // Here you would typically send the invoice email
        // For now, we'll just update the status
        
        return redirect()->route('invoices.show', $invoice)
                         ->with('success', 'Invoice sent successfully.');
    }
}