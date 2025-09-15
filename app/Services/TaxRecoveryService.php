<?php

namespace App\Services;

use App\Models\TaxPayment;
use App\Models\TaxCollection;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\Quotation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TaxRecoveryService
{
    /**
     * Register tax payment from a purchase order.
     */
    public function registerTaxPayment(PurchaseOrder $purchaseOrder): TaxPayment
    {
        return TaxPayment::create([
            'purchase_order_id' => $purchaseOrder->purchase_order_id,
            'tax_rate_id' => $purchaseOrder->tax_rate_id,
            'taxable_amount' => $purchaseOrder->subtotal,
            'tax_amount' => $purchaseOrder->tax_amount,
            'payment_type' => 'import',
            'payment_date' => $purchaseOrder->order_date,
            'document_number' => $purchaseOrder->purchase_order_number,
            'supplier_name' => $purchaseOrder->supplier->name ?? null,
            'description' => "IVA pagado en orden de compra {$purchaseOrder->purchase_order_number}",
            'status' => 'paid',
            'created_by_user_id' => $purchaseOrder->created_by_user_id,
        ]);
    }

    /**
     * Register tax collection from an invoice.
     */
    public function registerTaxCollection(Invoice $invoice): TaxCollection
    {
        return TaxCollection::create([
            'invoice_id' => $invoice->invoice_id,
            'tax_rate_id' => $invoice->tax_rate_id,
            'taxable_amount' => $invoice->subtotal,
            'tax_amount' => $invoice->tax_amount,
            'collection_type' => 'sale',
            'collection_date' => $invoice->invoice_date,
            'customer_name' => $invoice->customer->name ?? null,
            'description' => "IVA cobrado en factura {$invoice->invoice_number}",
            'status' => 'collected',
            'created_by_user_id' => $invoice->created_by_user_id,
        ]);
    }

    /**
     * Register tax collection from a quotation.
     */
    public function registerTaxCollectionFromQuotation(Quotation $quotation): TaxCollection
    {
        return TaxCollection::create([
            'quotation_id' => $quotation->quotation_id,
            'tax_rate_id' => $quotation->tax_rate_id,
            'taxable_amount' => $quotation->subtotal,
            'tax_amount' => $quotation->tax_amount,
            'collection_type' => 'sale',
            'collection_date' => $quotation->quotation_date,
            'customer_name' => $quotation->opportunity->customer->name ?? null,
            'description' => "IVA cobrado en cotización {$quotation->subject}",
            'status' => 'collected',
            'created_by_user_id' => $quotation->created_by_user_id,
        ]);
    }

    /**
     * Get tax summary for a specific period.
     */
    public function getTaxSummary(string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $user = Auth::user();
        $tenantId = config('tenant_id') ?: ($user?->tenant_id);

        $paymentsQuery = TaxPayment::whereBetween('payment_date', [$start, $end])
            ->where('status', 'paid');
        $collectionsQuery = TaxCollection::whereBetween('collection_date', [$start, $end])
            ->where('status', 'collected');

        // Scope by tenant for non-super-admin users
        if (!($user && (bool) ($user->is_super_admin ?? false))) {
            if ($tenantId) {
                $paymentsQuery->where('tenant_id', $tenantId);
                $collectionsQuery->where('tenant_id', $tenantId);
            }
        }

        $taxPayments = $paymentsQuery->get();
        $taxCollections = $collectionsQuery->get();

        $totalTaxPaid = $taxPayments->sum('tax_amount');
        $totalTaxCollected = $taxCollections->sum('tax_amount');
        $netTaxOwed = $totalTaxCollected - $totalTaxPaid;

        return [
            'period' => [
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d'),
            ],
            'tax_paid' => [
                'total' => $totalTaxPaid,
                'count' => $taxPayments->count(),
                'breakdown' => $this->getTaxBreakdown($taxPayments),
            ],
            'tax_collected' => [
                'total' => $totalTaxCollected,
                'count' => $taxCollections->count(),
                'breakdown' => $this->getTaxBreakdown($taxCollections),
            ],
            'net_tax' => [
                'amount' => $netTaxOwed,
                'status' => $netTaxOwed >= 0 ? 'payable' : 'refundable',
            ],
        ];
    }

    /**
     * Get tax breakdown by rate.
     */
    private function getTaxBreakdown($transactions): array
    {
        return $transactions->groupBy('tax_rate_id')
            ->map(function ($group) {
                $first = $group->first();
                $taxRate = $first?->taxRate;
                return [
                    'tax_rate_name' => $taxRate->name ?? 'Unknown',
                    'tax_rate_percentage' => $taxRate->rate ?? null,
                    'count' => $group->count(),
                    'total_amount' => $group->sum('tax_amount'),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Generate monthly tax report.
     */
    public function generateMonthlyReport(int $year, int $month): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        return $this->getTaxSummary($startDate->format('Y-m-d'), $endDate->format('Y-m-d'));
    }

    /**
     * Get pending tax recoveries.
     */
    public function getPendingRecoveries(): \Illuminate\Database\Eloquent\Collection
    {
        return TaxPayment::where('status', 'paid')
            ->whereNull('recovery_date')
            ->with(['purchaseOrder', 'taxRate'])
            ->orderBy('payment_date', 'asc')
            ->get();
    }

    /**
     * Get pending tax remittances.
     */
    public function getPendingRemittances(): \Illuminate\Database\Eloquent\Collection
    {
        return TaxCollection::where('status', 'collected')
            ->whereNull('remittance_date')
            ->with(['invoice', 'taxRate'])
            ->orderBy('collection_date', 'asc')
            ->get();
    }

    /**
     * Mark tax payments as recovered.
     */
    public function markTaxPaymentsAsRecovered(array $taxPaymentIds): int
    {
        return TaxPayment::whereIn('tax_payment_id', $taxPaymentIds)
            ->update([
                'status' => 'recovered',
                'recovery_date' => now(),
            ]);
    }

    /**
     * Mark tax collections as remitted.
     */
    public function markTaxCollectionsAsRemitted(array $taxCollectionIds): int
    {
        return TaxCollection::whereIn('tax_collection_id', $taxCollectionIds)
            ->update([
                'status' => 'remitted',
                'remittance_date' => now(),
            ]);
    }

    /**
     * Get tax statistics for dashboard.
     */
    public function getTaxStatistics(): array
    {
        $currentMonth = Carbon::now()->startOfMonth();
        $currentYear = Carbon::now()->startOfYear();

        return [
            'current_month' => $this->getTaxSummary(
                $currentMonth->format('Y-m-d'),
                Carbon::now()->format('Y-m-d')
            ),
            'current_year' => $this->getTaxSummary(
                $currentYear->format('Y-m-d'),
                Carbon::now()->format('Y-m-d')
            ),
            'pending_recoveries' => $this->getPendingRecoveries()->count(),
            'pending_remittances' => $this->getPendingRemittances()->count(),
        ];
    }
} 