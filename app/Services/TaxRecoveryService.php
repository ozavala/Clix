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
    public function generateMonthlyReport(int $year, int $month, ?int $tenantId = null): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        
        $query = $this->buildBaseQuery($startDate, $endDate, $tenantId);
        
        // Add monthly specific logic here
        $report = $query->get();
        
        return [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
                'start_formatted' => $startDate->format('M d, Y'),
                'end_formatted' => $endDate->format('M d, Y'),
                'month' => $month,
                'year' => $year,
            ],
            'summary' => $this->calculateSummary($report),
            'details' => $report->groupBy('tax_rate_id')
        ];
    }
    
    /**
     * Generate annual tax report.
     */
    public function generateAnnualReport(int $year, ?int $tenantId = null): array
    {
        $startDate = Carbon::create($year, 1, 1)->startOfYear();
        $endDate = $startDate->copy()->endOfYear();
        
        $monthlyReports = [];
        $yearlySummary = [
            'total_tax_collected' => 0,
            'total_tax_paid' => 0,
            'net_tax_balance' => 0,
            'months' => []
        ];
        
        // Generate report for each month
        for ($month = 1; $month <= 12; $month++) {
            $monthReport = $this->generateMonthlyReport($year, $month, $tenantId);
            $monthlyReports[$month] = $monthReport;
            
            // Aggregate yearly summary
            $yearlySummary['total_tax_collected'] += $monthReport['summary']['tax_collected']['total'];
            $yearlySummary['total_tax_paid'] += $monthReport['summary']['tax_paid']['total'];
            $yearlySummary['net_tax_balance'] += $monthReport['summary']['tax_collected']['total'] - $monthReport['summary']['tax_paid']['total'];
            $yearlySummary['months'][$month] = $monthReport['summary'];
        }
        
        return [
            'year' => $year,
            'monthly_reports' => $monthlyReports,
            'yearly_summary' => $yearlySummary
        ];
    }
    
    /**
     * Generate custom date range tax report.
     */
    public function generateCustomReport(string $startDate, string $endDate, ?int $tenantId = null): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();
        
        $query = $this->buildBaseQuery($start, $end, $tenantId);
        $report = $query->get();
        
        return [
            'period' => [
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d'),
                'start_formatted' => $start->format('M d, Y'),
                'end_formatted' => $end->format('M d, Y'),
            ],
            'summary' => $this->calculateSummary($report),
            'details' => $report->groupBy('tax_rate_id')
        ];
    }
    
    /**
     * Get dashboard data.
     */
    public function getDashboardData(?int $tenantId = null): array
    {
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();
        
        // Current month data
        $currentMonthQuery = $this->buildBaseQuery($startOfMonth, $endOfMonth, $tenantId);
        $currentMonthData = $this->calculateSummary($currentMonthQuery->get());
        
        // Previous month data
        $prevMonthStart = $startOfMonth->copy()->subMonth();
        $prevMonthEnd = $endOfMonth->copy()->subMonth();
        $prevMonthQuery = $this->buildBaseQuery($prevMonthStart, $prevMonthEnd, $tenantId);
        $prevMonthData = $this->calculateSummary($prevMonthQuery->get());
        
        // Year to date data
        $startOfYear = $now->copy()->startOfYear();
        $ytdQuery = $this->buildBaseQuery($startOfYear, $now, $tenantId);
        $ytdData = $this->calculateSummary($ytdQuery->get());
        
        return [
            'current_month' => $currentMonthData,
            'previous_month' => $prevMonthData,
            'year_to_date' => $ytdData,
            'periods' => [
                'current_month' => [
                    'start' => $startOfMonth->format('Y-m-d'),
                    'end' => $endOfMonth->format('Y-m-d'),
                ],
                'previous_month' => [
                    'start' => $prevMonthStart->format('Y-m-d'),
                    'end' => $prevMonthEnd->format('Y-m-d'),
                ],
                'year_to_date' => [
                    'start' => $startOfYear->format('Y-m-d'),
                    'end' => $now->format('Y-m-d'),
                ]
            ]
        ];
    }
    
    /**
     * Build base query for tax reports.
     */
    protected function buildBaseQuery(Carbon $start, Carbon $end, ?int $tenantId = null)
    {
        $user = Auth::user();
        $isSuperAdmin = $user && (bool) ($user->is_super_admin ?? false);
        
        // Create base query for tax collections
        $collectionsQuery = DB::table('tax_collections as tc')
            ->select(
                'tc.tax_collection_id as id',
                'tc.collection_date',
                'tc.taxable_amount',
                'tc.tax_amount',
                'tc.collection_type',
                'tr.name as tax_rate_name',
                'tr.rate as tax_rate',
                DB::raw('"collection" as record_type')
            )
            ->leftJoin('tax_rates as tr', 'tc.tax_rate_id', '=', 'tr.tax_rate_id')
            ->where('tc.status', 'collected')
            ->whereBetween('tc.collection_date', [$start, $end]);
            
        // Create base query for tax payments
        $paymentsQuery = DB::table('tax_payments as tp')
            ->select(
                'tp.tax_payment_id as id',
                'tp.payment_date as collection_date',
                'tp.taxable_amount',
                'tp.tax_amount',
                'tp.payment_type as collection_type',
                'tr.name as tax_rate_name',
                'tr.rate as tax_rate',
                DB::raw('"payment" as record_type')
            )
            ->leftJoin('tax_rates as tr', 'tp.tax_rate_id', '=', 'tr.tax_rate_id')
            ->where('tp.status', 'paid')
            ->whereBetween('tp.payment_date', [$start, $end]);
            
        if (!$isSuperAdmin || $tenantId) {
            $tenantToFilter = $tenantId ?? ($user->tenant_id ?? null);
            if ($tenantToFilter) {
                $collectionsQuery->where('tc.tenant_id', $tenantToFilter);
                $paymentsQuery->where('tp.tenant_id', $tenantToFilter);
            }
        }
        
        return $collectionsQuery->unionAll($paymentsQuery);
    }
    
    /**
     * Calculate summary from query results.
     */
    protected function calculateSummary($results)
    {
        $collections = $results->where('record_type', 'collection');
        $payments = $results->where('record_type', 'payment');
        
        $taxCollected = $collections->sum('tax_amount');
        $taxPaid = $payments->sum('tax_amount');
        $taxableCollected = $collections->sum('taxable_amount');
        $taxablePaid = $payments->sum('taxable_amount');
        
        return [
            'tax_paid' => [
                'total' => $taxPaid,
                'count' => $payments->count(),
                'taxable_amount' => $taxablePaid
            ],
            'tax_collected' => [
                'total' => $taxCollected,
                'count' => $collections->count(),
                'taxable_amount' => $taxableCollected
            ],
            'net_tax_balance' => $taxCollected - $taxPaid
        ];
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