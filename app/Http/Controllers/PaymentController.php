<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\Invoice; // To update invoice amount_paid
use App\Models\PurchaseOrder; // To update PO amount_paid (if implemented directly)
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Http\Requests\StorePaymentRequest;
// We might not need UpdatePaymentRequest if payments are immutable or handled differently
// use App\Http\Requests\UpdatePaymentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use App\Models\Customer; // If you need to link payments to customers
use App\Models\Account;

class PaymentController extends Controller
{
    public function index(Request $request)
    {   
        $payments = Payment::with(['payable', 'createdBy'])->paginate(10);
        return view('payments.index', compact('payments'));
    }
    
    /**
     * Store a newly created resource in storage.
     * This method will be generic but primarily used via specific routes
     * e.g., POST /invoices/{invoice}/payments
     */
    public function store(StorePaymentRequest $request)
    {
        $validatedData = $request->validated();
        $validatedData['created_by_user_id'] = Auth::id();

        $payableType = $validatedData['payable_type'];
        $payableId = $validatedData['payable_id'];

        // Find the payable model (Invoice or PurchaseOrder)
        $payableModel = null;
        if ($payableType === Invoice::class) {
            $payableModel = Invoice::find($payableId);
        } elseif ($payableType === Order::class) {
            $payableModel = Order::find($payableId);
        } elseif ($payableType === PurchaseOrder::class) {
            // This flow should now be deprecated in favor of paying Bills.
            // Keeping it for now, but ideally, you'd remove this and the related routes.
            // Enable linking payments directly to PurchaseOrder
            $payableModel = PurchaseOrder::find($payableId);
        } elseif ($payableType === Bill::class) {
            $payableModel = Bill::find($payableId);
        }

        if (!$payableModel) {
            return back()->with('error', 'Invalid payable entity specified.');
        }

        // Check if payment amount exceeds amount due
        if (method_exists($payableModel, 'getAmountDueAttribute')) {
            $amountDue = $payableModel->amount_due; // Using the accessor consistently
            if (bccomp($validatedData['amount'], $amountDue, 2) === 1) {
                return back()->withInput()->with('error', 'Payment amount cannot exceed the amount due.');
            }
        } elseif ($payableModel instanceof PurchaseOrder) {
            $amountDue = $payableModel->amount_due; // Using the accessor from the model
            if (bccomp($validatedData['amount'], $amountDue, 2) === 1) {
                return back()->withInput()->with('error', 'Payment amount cannot exceed the amount due for the Purchase Order.');
            }
        } elseif ($payableModel instanceof Bill) {
            $amountDue = $payableModel->amount_due;
            if (bccomp($validatedData['amount'], $amountDue, 2) === 1) {
                return back()->withInput()->with('error', 'Payment amount cannot exceed the amount due for the Bill.');
            }
        }

        DB::transaction(function () use ($validatedData, $payableModel) {
            // Ensure the tenant_id is set from the payable model
            $validatedData['tenant_id'] = $payableModel->tenant_id ?? null;
            
            $payment = Payment::create($validatedData);
            $this->createJournalEntriesForPayment($payment, $payableModel);
            // The PaymentObserver will now handle updating the payable model's status and amount_paid.
        });

        // Redirect back to the payable entity's show page
        if ($payableModel instanceof Invoice) {
            return redirect()->route('invoices.show', $payableModel)
                             ->with('success', 'Payment recorded successfully.');
        } elseif ($payableModel instanceof Order) {
            return redirect()->route('orders.show', $payableModel)
                             ->with('success', 'Payment recorded successfully.');
        } elseif ($payableModel instanceof PurchaseOrder) {
            return redirect()->route('purchase-orders.show', $payableModel)
                             ->with('success', 'Payment recorded successfully.');
        } elseif ($payableModel instanceof Bill) {
            return redirect()->route('bills.show', $payableModel)
                             ->with('success', 'Payment recorded successfully.');
        }

        return redirect()->back()->with('success', 'Payment recorded successfully.'); // Fallback
    }
    public function show(Payment $payment)
    {
        $payable = $payment->payable;
        return view('payments.show', compact('payable'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customers = Customer::orderBy('first_name')->get();
        $invoices = Invoice::with('customer')->orderBy('invoice_date', 'desc')->get();
        $orders = Order::with('customer')->orderBy('order_date', 'desc')->get();
        
        return view('payments.create', compact('customers', 'invoices', 'orders'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Payment $payment)
    {
        $customers = Customer::orderBy('first_name')->get();
        $invoices = Invoice::with('customer')->orderBy('invoice_date', 'desc')->get();
        $orders = Order::with('customer')->orderBy('order_date', 'desc')->get();
        
        return view('payments.edit', compact('payment', 'customers', 'invoices', 'orders'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Payment $payment)
    {
        $validatedData = $request->validate([
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string|max:255',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $payment->update($validatedData);

        return redirect()->route('payments.index')
                         ->with('success', 'Payment updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     * Typically, deleting a payment should also adjust the parent's paid amount.
     */
    public function destroy(Payment $payment)
    {
        $payable = $payment->payable; // Get the related Invoice or PurchaseOrder

        DB::transaction(function () use ($payment, $payable) {
            // TODO: Consider how to reverse or mark journal entries related to this deleted payment.
            $payment->delete();
            // The PaymentObserver will now handle updating the payable model's status and amount_paid.
        });

        if ($payable instanceof Invoice) {
            return redirect()->route('invoices.show', $payable)
                             ->with('success', 'Payment deleted successfully and invoice updated.');
        } elseif ($payable instanceof Order) {
            return redirect()->route('orders.show', $payable)
                             ->with('success', 'Payment deleted successfully and order updated.');
        } elseif ($payable instanceof PurchaseOrder) {
            return redirect()->route('purchase-orders.show', $payable)
                             ->with('success', 'Payment deleted successfully and purchase order updated.');
        } elseif ($payable instanceof Bill) {
            return redirect()->route('bills.show', $payable)
                             ->with('success', 'Payment deleted successfully and purchase order updated.');
        }

        return redirect()->back()->with('success', 'Payment deleted successfully.'); // Fallback
    }

    /**
     * Create journal entries for a given payment.
     */
    protected function createJournalEntriesForPayment(Payment $payment, Model $payableModel)
    {
        // Get company settings for legal_id
        $companyLegalId = \App\Models\Setting::where('key', 'company_legal_id')
            ->where('tenant_id', $payment->tenant_id)
            ->first()?->value ?? 'N/A';
        
        $journalEntry = JournalEntry::create([
            'tenant_id' => $payment->tenant_id,
            'entry_date' => $payment->payment_date,
            'transaction_type' => 'Payment',
            'description' => "Payment for " . class_basename($payableModel) . " #" . ($payableModel->invoice_number ?? $payableModel->purchase_order_number ?? $payableModel->bill_number ?? $payableModel->getKey()),
            'referenceable_id' => $payment->payment_id,
            'referenceable_type' => Payment::class,
            'created_by_user_id' => $payment->created_by_user_id,
        ]);

        // Helper para obtener el nombre de la cuenta
        $getAccountName = function($code) {
            $map = [
                '2101' => 'Accounts Payable',
                '1101' => 'Bank',
                '2102' => 'Accounts Receivable',
            ];
            return optional(\App\Models\Account::where('code', $code)->first())->name ?? ($map[$code] ?? $code);
        };

        if ($payableModel instanceof Invoice || $payableModel instanceof Order) {
            // Pago de factura/orden (cliente paga a la empresa)
            // Debe: Bancos/Caja (empresa principal), Haber: Cuentas por cobrar (cliente)
            $customer = $payableModel->customer;
            $customerLegalId = $customer->legal_id ?? 'N/A';
            
            $journalEntry->lines()->createMany([
                [
                    'tenant_id' => $payment->tenant_id,
                    'account_code' => '1101',
                    'account_name' => $getAccountName('1101') . ' (' . $companyLegalId . ')',
                    'debit_amount' => $payment->amount,
                    'credit_amount' => 0,
                    'description' => 'Bank account of main company',
                ],
                [
                    'tenant_id' => $payment->tenant_id,
                    'account_code' => '2102',
                    'account_name' => $getAccountName('2102') . ' (' . $customerLegalId . ')',
                    'debit_amount' => 0,
                    'credit_amount' => $payment->amount,
                    'entity_id' => $payableModel->customer_id,
                    'entity_type' => Customer::class,
                    'description' => 'Customer accounts receivable',
                ]
            ]);
        } elseif ($payableModel instanceof Bill || $payableModel instanceof PurchaseOrder) {
            // Pago de factura/orden de compra (empresa paga a proveedor)
            // Debe: Cuentas por pagar (proveedor), Haber: Bancos/Caja (empresa principal)
            $supplier = $payableModel->supplier;
            $supplierLegalId = $supplier->legal_id ?? 'N/A';
            
            $journalEntry->lines()->createMany([
                [
                    'tenant_id' => $payment->tenant_id,
                    'account_code' => '2101',
                    'account_name' => $getAccountName('2101') . ' (' . $supplierLegalId . ')',
                    'debit_amount' => $payment->amount,
                    'credit_amount' => 0,
                    'entity_id' => $payableModel->supplier_id,
                    'entity_type' => \App\Models\Supplier::class,
                    'description' => 'Supplier accounts payable',
                ],
                [
                    'tenant_id' => $payment->tenant_id,
                    'account_code' => '1101',
                    'account_name' => $getAccountName('1101') . ' (' . $companyLegalId . ')',
                    'debit_amount' => 0,
                    'credit_amount' => $payment->amount,
                    'description' => 'Bank account of main company',
                ]
            ]);
        }
    }

    // Other methods like index, create, show, edit, update might not be directly used
    // for payments in a standalone way, or would need context (e.g., payments for a specific invoice).
}