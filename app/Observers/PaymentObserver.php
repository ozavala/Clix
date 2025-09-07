<?php

namespace App\Observers;

use App\Models\Payment;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;

class PaymentObserver
{
    /**
     * Handle the Payment "created" event.
     * This is triggered after a payment is saved.
     */
    public function created(Payment $payment): void
    {
        if (method_exists($payment->payable, 'updateStatusAfterPayment')) {
            $payment->payable->updateStatusAfterPayment();
        }

        // Asiento contable automático para pagos
        $payable = $payment->payable;
        $tipo = $payable ? class_basename($payable) : null;
        $desc = 'Pago de ' . ($tipo === 'Invoice' ? 'factura' : ($tipo === 'Bill' ? 'compra' : 'documento'));
        $entry = JournalEntry::create([
            'tenant_id' => $payment->tenant_id,
            'entry_date' => $payment->payment_date ?? now(),
            'transaction_type' => 'payment',
            'description' => $desc . ' (ID: ' . $payment->payment_id . ')',
            'referenceable_id' => $payment->payment_id,
            'referenceable_type' => Payment::class,
            'created_by_user_id' => $payment->created_by_user_id,
        ]);

        if ($tipo === 'Invoice') {
            // Pago de factura (cliente)
            // Debe: Bancos/Caja, Haber: Cuentas por cobrar
            JournalEntryLine::create([
                'tenant_id' => $payment->tenant_id,
                'journal_entry_id' => $entry->journal_entry_id,
                'account_code' => '1101',
                'debit_amount' => $payment->amount,
                'credit_amount' => 0,
            ]);
            JournalEntryLine::create([
                'tenant_id' => $payment->tenant_id,
                'journal_entry_id' => $entry->journal_entry_id,
                'account_code' => '2102',
                'debit_amount' => 0,
                'credit_amount' => $payment->amount,
            ]);
        } elseif ($tipo === 'Bill') {
            // Pago de compra (proveedor)
            // Debe: Cuentas por pagar, Haber: Bancos/Caja
            JournalEntryLine::create([
                'tenant_id' => $payment->tenant_id,
                'journal_entry_id' => $entry->journal_entry_id,
                'account_code' => '2101',
                'debit_amount' => $payment->amount,
                'credit_amount' => 0,
            ]);
            JournalEntryLine::create([
                'tenant_id' => $payment->tenant_id,
                'journal_entry_id' => $entry->journal_entry_id,
                'account_code' => '1101',
                'debit_amount' => 0,
                'credit_amount' => $payment->amount,
            ]);
        }
    }

    /**
     * Handle the Payment "deleted" event.
     */
    public function deleted(Payment $payment): void
    {
        if (method_exists($payment->payable, 'updateStatusAfterPayment')) {
            $payment->payable->updateStatusAfterPayment();
        }
    }
}