<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\Auth;

class InvoiceObserver
{
    /**
     * Handle the Invoice "created" event.
     */
    public function created(Invoice $invoice)
    {
        // Ensure a valid created_by_user_id
        $created_by_user_id = Auth::id();
        if (!$created_by_user_id) {
            // Create a CrmUser for the current tenant if no authenticated user
            $user = \App\Models\CrmUser::factory()->forTenant($invoice->tenant)->create();
            $created_by_user_id = $user->user_id;
        }

        // Crear asiento principal
        $entry = JournalEntry::create([
            'tenant_id' => $invoice->tenant_id,
            'entry_date' => $invoice->invoice_date ?? now(),
            'transaction_type' => 'invoice',
            'description' => 'Registro de factura ' . $invoice->invoice_number,
            'referenceable_id' => $invoice->invoice_id,
            'referenceable_type' => Invoice::class,
            'created_by_user_id' => $created_by_user_id,
        ]);

        // Línea debe: Clientes (Cuentas por cobrar)
        JournalEntryLine::create([
            'tenant_id' => $invoice->tenant_id,
            'journal_entry_id' => $entry->journal_entry_id,
            'account_code' => '2102',
            'debit_amount' => $invoice->total_amount,
            'credit_amount' => 0,
        ]);

        // Línea haber: Ventas/Ingresos
        JournalEntryLine::create([
            'tenant_id' => $invoice->tenant_id,
            'journal_entry_id' => $entry->journal_entry_id,
            'account_code' => '3101',
            'debit_amount' => 0,
            'credit_amount' => $invoice->subtotal,
        ]);

        // Línea haber: Impuestos por pagar (si aplica)
        if ($invoice->tax_amount > 0) {
            JournalEntryLine::create([
                'tenant_id' => $invoice->tenant_id,
                'journal_entry_id' => $entry->journal_entry_id,
                'account_code' => '5101',
                'debit_amount' => 0,
                'credit_amount' => $invoice->tax_amount,
            ]);
        }
    }
} 