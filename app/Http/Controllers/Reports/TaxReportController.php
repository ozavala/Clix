<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\TaxRecoveryService;
use Illuminate\Support\Facades\Validator;
use App\Models\Tenant;

class TaxReportController extends Controller
{
    protected TaxRecoveryService $taxRecoveryService;

    public function __construct(TaxRecoveryService $taxRecoveryService)
    {
        $this->taxRecoveryService = $taxRecoveryService;
    }

    /**
     * Reporte mensual de IVA
     */
    public function monthly(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'year' => 'required|integer|min:2000|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'tenant_id' => 'nullable|exists:tenants,id',
        ])->sometimes('year', 'nullable', fn() => !$request->has('year'))
          ->sometimes('month', 'nullable', fn() => !$request->has('month'))
          ->validate();

        $report = null;
        $tenants = [];
        $selectedTenantId = null;

        if ($request->has(['year', 'month'])) {
            $report = $this->taxRecoveryService->generateMonthlyReport(
                $request->input('year'), 
                $request->input('month'),
                $request->input('tenant_id')
            );
            
            // If super admin, get tenants for filter
            if (auth()->user()->is_super_admin) {
                $tenants = Tenant::orderBy('name')->get(['id', 'name']);
                $selectedTenantId = $request->input('tenant_id');
            }
        }

        if ($request->wantsJson() || $request->ajax() || $request->query('format') === 'json') {
            // Format the response to match the expected structure in tests
            $response = [
                'tax_paid' => [
                    'total' => $report['summary']['tax_paid']['total'] ?? 0,
                ],
                'tax_collected' => [
                    'total' => $report['summary']['tax_collected']['total'] ?? 0,
                ],
                'period' => $report['period'] ?? [],
                'summary' => $report['summary'] ?? [],
                'details' => $report['details'] ?? []
            ];
            
            return response()->json($response);
        }
        
        return view('reports.iva.mensual', compact('report', 'tenants', 'selectedTenantId'));
    }

    /**
     * Reporte anual de IVA
     */
    public function annual(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'year' => 'required|integer|min:2000|max:2100',
            'tenant_id' => 'nullable|exists:tenants,id',
        ])->sometimes('year', 'nullable', fn() => !$request->has('year'))
          ->validate();

        $report = null;
        $tenants = [];
        $selectedTenantId = null;
        $year = $request->input('year');

        if ($year) {
            $report = $this->taxRecoveryService->generateAnnualReport(
                $year,
                $request->input('tenant_id')
            );
            
            // If super admin, get tenants for filter
            if (auth()->user()->is_super_admin) {
                $tenants = Tenant::orderBy('name')->get(['id', 'name']);
                $selectedTenantId = $request->input('tenant_id');
            }
        }

        if ($request->wantsJson() || $request->ajax() || $request->query('format') === 'json') {
            return response()->json($report);
        }
        
        return view('reports.iva.anual', compact('report', 'year', 'tenants', 'selectedTenantId'));
    }

    /**
     * Reporte personalizado de IVA
     */
    public function custom(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'tenant_id' => 'nullable|exists:tenants,id',
        ])->validate();

        $report = $this->taxRecoveryService->generateCustomReport(
            $request->input('start_date'),
            $request->input('end_date'),
            $request->input('tenant_id')
        );

        $tenants = [];
        $selectedTenantId = null;
        
        // If super admin, get tenants for filter
        if (auth()->user()->is_super_admin) {
            $tenants = Tenant::orderBy('name')->get(['id', 'name']);
            $selectedTenantId = $request->input('tenant_id');
        }

        if ($request->wantsJson() || $request->ajax() || $request->query('format') === 'json') {
            return response()->json($report);
        }
        
        return view('reports.iva.custom', array_merge($report, [
            'tenants' => $tenants,
            'selectedTenantId' => $selectedTenantId
        ]));
    }

    /**
     * Dashboard de IVA
     */
    public function dashboard(Request $request)
    {
        $tenants = [];
        $selectedTenantId = null;
        
        // If super admin, get tenants for filter
        if (auth()->user()->is_super_admin) {
            $tenants = Tenant::orderBy('name')->get(['id', 'name']);
            $selectedTenantId = $request->input('tenant_id');
        }
        
        $dashboardData = $this->taxRecoveryService->getDashboardData($selectedTenantId);
        
        if ($request->wantsJson() || $request->ajax() || $request->query('format') === 'json') {
            return response()->json($dashboardData);
        }
        
        return view('reports.iva.dashboard', array_merge($dashboardData, [
            'tenants' => $tenants,
            'selectedTenantId' => $selectedTenantId
        ]));
    }
}
