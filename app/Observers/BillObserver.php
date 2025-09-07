<?php

namespace App\Observers;

use App\Models\Bill;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\Auth;

class BillObserver
{
    /**
     * Handle the Bill "created" event.
     */
    public function created(Bill $bill)
    {
        // Crear asiento principal
        $entry = JournalEntry::create([
            'tenant_id' => $bill->tenant_id,
            'entry_date' => $bill->bill_date ?? now(),
            'transaction_type' => 'bill',
            'description' => 'Registro de compra ' . $bill->bill_number,
            'referenceable_id' => $bill->bill_id,
            'referenceable_type' => Bill::class,
            'created_by_user_id' => $bill->created_by_user_id,
        ]);

        // Línea debe: Inventario/Costos
        JournalEntryLine::create([
            'tenant_id' => $bill->tenant_id,
            'journal_entry_id' => $entry->journal_entry_id,
            'account_code' => '4101',
            'debit_amount' => $bill->total_amount,
            'credit_amount' => 0,
        ]);

        // Línea haber: Proveedores/Cuentas por pagar
        JournalEntryLine::create([
            'tenant_id' => $bill->tenant_id,
            'journal_entry_id' => $entry->journal_entry_id,
            'account_code' => '2101',
            'debit_amount' => 0,
            'credit_amount' => $bill->total_amount,
        ]);
    }
} 