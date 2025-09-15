<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Bill;
use App\Models\TaxCollection;
use App\Models\TaxPayment;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class TaxBalanceController extends Controller
{
    /**
     * Display the tax balance report
     */
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));
        $tenantId = $request->input('tenant_id');
        
        $report = $this->generateTaxBalanceReport($startDate, $endDate, $tenantId);
        
        $tenants = [];
        $requestedTenantId = null;
        
        if (auth()->user()->is_super_admin) {
            $tenants = Tenant::orderBy('name')->get(['id', 'name']);
            $requestedTenantId = $tenantId;
        }
        
        return view('reports.tax_balance.index', compact(
            'report', 
            'startDate', 
            'endDate',
            'tenants',
            'requestedTenantId'
        ));
    }

    /**
     * Generate tax balance report for a specific period
     */
    private function generateTaxBalanceReport(string $startDate, string $endDate, ?int $tenantId = null): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        // Base query for invoices with tenant filter if provided
        $invoiceQuery = Invoice::whereBetween('invoice_date', [$start, $end])
            ->where('status', '!=', 'Void')
            ->where('status', '!=', 'Cancelled');

        // Base query for bills with tenant filter if provided
        $billQuery = Bill::whereBetween('bill_date', [$start, $end])
            ->where('status', '!=', 'Cancelled');

        // Apply tenant filter if provided and user is super admin
        if ($tenantId && auth()->user()->is_super_admin) {
            $invoiceQuery->where('tenant_id', $tenantId);
            $billQuery->where('tenant_id', $tenantId);
        }

        // Impuestos de venta recibidos (VAT Collected)
        $salesTaxCollected = (clone $invoiceQuery)
            ->select(
                DB::raw('SUM(tax_amount) as total_tax_collected'),
                DB::raw('COUNT(*) as total_invoices'),
                DB::raw('SUM(subtotal) as total_taxable_amount')
            )
            ->first();

        // Impuestos de compra pagados (VAT Paid)
        $purchaseTaxPaid = (clone $billQuery)
            ->select(
                DB::raw('SUM(tax_amount) as total_tax_paid'),
                DB::raw('COUNT(*) as total_bills'),
                DB::raw('SUM(subtotal) as total_taxable_amount')
            )
            ->first();

        // Detalle por tasa de impuesto - Ventas
        $salesTaxByRate = (clone $invoiceQuery)
            ->whereNotNull('tax_rate_id')
            ->join('tax_rates', 'invoices.tax_rate_id', '=', 'tax_rates.tax_rate_id')
            ->select(
                'tax_rates.name as tax_rate_name',
                'tax_rates.rate as tax_rate_percentage',
                DB::raw('SUM(invoices.tax_amount) as total_tax_collected'),
                DB::raw('COUNT(*) as invoice_count'),
                DB::raw('SUM(invoices.subtotal) as total_taxable_amount')
            )
            ->groupBy('tax_rates.tax_rate_id', 'tax_rates.name', 'tax_rates.rate')
            ->orderBy('tax_rates.rate', 'desc')
            ->get();

        // Detalle por tasa de impuesto - Compras
        $purchaseTaxByRate = (clone $billQuery)
            ->join('purchase_orders', 'bills.purchase_order_id', '=', 'purchase_orders.purchase_order_id')
            ->select(
                DB::raw('purchase_orders.tax_percentage as tax_rate_percentage'),
                DB::raw('CONCAT("Tax Rate ", purchase_orders.tax_percentage, "%") as tax_rate_name'),
                DB::raw('SUM(bills.tax_amount) as total_tax_paid'),
                DB::raw('COUNT(*) as bill_count'),
                DB::raw('SUM(bills.subtotal) as total_taxable_amount')
            )
            ->groupBy('purchase_orders.tax_percentage')
            ->orderBy('purchase_orders.tax_percentage', 'desc')
            ->get();

        // Top 10 clientes por impuestos pagados
        $topCustomersByTax = (clone $invoiceQuery)
            ->join('customers', 'invoices.customer_id', '=', 'customers.customer_id')
            ->select(
                'customers.company_name as customer_name',
                'customers.first_name',
                'customers.last_name',
                DB::raw('SUM(invoices.tax_amount) as total_tax_collected'),
                DB::raw('COUNT(*) as invoice_count')
            )
            ->groupBy('customers.customer_id', 'customers.company_name', 'customers.first_name', 'customers.last_name')
            ->orderBy('total_tax_collected', 'desc')
            ->limit(10)
            ->get();

        // Top 10 proveedores por impuestos pagados
        $topSuppliersByTax = (clone $billQuery)
            ->join('suppliers', 'bills.supplier_id', '=', 'suppliers.supplier_id')
            ->select(
                'suppliers.name as supplier_name',
                'suppliers.contact_person',
                DB::raw('SUM(bills.tax_amount) as total_tax_paid'),
                DB::raw('COUNT(*) as bill_count')
            )
            ->groupBy('suppliers.supplier_id', 'suppliers.name', 'suppliers.contact_person')
            ->orderBy('total_tax_paid', 'desc')
            ->limit(10)
            ->get();

        // Cálculo del balance neto
        $totalTaxCollected = $salesTaxCollected->total_tax_collected ?? 0;
        $totalTaxPaid = $purchaseTaxPaid->total_tax_paid ?? 0;
        $netTaxBalance = $totalTaxCollected - $totalTaxPaid;

        return [
            'period' => [
                'start_date' => $start->format('Y-m-d'),
                'end_date' => $end->format('Y-m-d'),
                'start_formatted' => $start->format('d/m/Y'),
                'end_formatted' => $end->format('d/m/Y'),
            ],
            'summary' => [
                'total_tax_collected' => $totalTaxCollected,
                'total_tax_paid' => $totalTaxPaid,
                'net_tax_balance' => $netTaxBalance,
                'balance_status' => $netTaxBalance >= 0 ? 'payable' : 'refundable',
                'total_invoices' => $salesTaxCollected->total_invoices ?? 0,
                'total_bills' => $purchaseTaxPaid->total_bills ?? 0,
            ],
            'sales_tax_by_rate' => $salesTaxByRate,
            'purchase_tax_by_rate' => $purchaseTaxByRate,
            'top_customers_by_tax' => $topCustomersByTax,
            'top_suppliers_by_tax' => $topSuppliersByTax,
        ];
    }

    /**
     * Export tax balance report to PDF
     */
    public function exportPdf(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));
        $tenantId = $request->input('tenant_id');
        
        $report = $this->generateTaxBalanceReport($startDate, $endDate, $tenantId);
        
        // Get tenant info if tenant_id is provided
        $selectedTenant = $tenantId ? Tenant::find($tenantId) : null;
        
        $pdf = Pdf::loadView('reports.tax_balance.pdf', [
            'report' => $report,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedTenant' => $selectedTenant,
        ]);
        
        $filename = 'balance-iva-' . $startDate . '-a-' . $endDate;
        if ($selectedTenant) {
            $filename .= '-' . Str::slug($selectedTenant->name);
        }
        $filename .= '.pdf';
        
        return $pdf->download($filename);
    }

    /**
     * Export tax balance report to Excel
     */
    public function exportExcel(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));
        $tenantId = $request->input('tenant_id');
        
        // Get tenant info if tenant_id is provided
        $selectedTenant = $tenantId ? Tenant::find($tenantId) : null;
        
        $filename = 'balance-iva-' . $startDate . '-a-' . $endDate;
        if ($selectedTenant) {
            $filename .= '-' . Str::slug($selectedTenant->name);
        }
        $filename .= '.xlsx';
        
        return (new TaxBalanceExport($startDate, $endDate, $tenantId))->download($filename);
    }
} 