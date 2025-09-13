<?php

namespace App\Http\Controllers;

use App\Models\JournalEntryLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfitAndLossController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->endOfMonth()->toDateString());

        $user = Auth::user();
        $currentTenantId = config('tenant_id');
        $filterTenantId = $request->input('tenant_id');

        $applyTenantScope = true;
        if ($user && property_exists($user, 'is_super_admin') && $user->is_super_admin) {
            // Super admin can aggregate across tenants unless a specific tenant_id is requested
            $applyTenantScope = $filterTenantId ? true : false;
        }

        // Ingresos: todas las cuentas tipo 'Ingreso'
        $ingresosQuery = JournalEntryLine::whereHas('account', function($q) {
            $q->whereIn('type', ['income', 'Ingreso']);
        })
        ->whereBetween('created_at', [$from, $to]);
        if ($applyTenantScope) {
            $ingresosQuery->where('tenant_id', $filterTenantId ?: $currentTenantId);
        }
        $ingresosDetalle = $ingresosQuery
            ->selectRaw('account_code, SUM(credit_amount) as total')
            ->groupBy('account_code')
            ->get();
        $ingresos = $ingresosDetalle->sum('total');

        // Costos: todas las cuentas tipo 'Gasto' cuyo nombre sea "Costo de ventas" o "Compras"
        $costosQuery = JournalEntryLine::whereHas('account', function($q) {
            $q->whereIn('type', ['expense', 'Gasto'])->whereIn('name', ['Costo de ventas', 'Compras']);
        })
        ->whereBetween('created_at', [$from, $to]);
        if ($applyTenantScope) {
            $costosQuery->where('tenant_id', $filterTenantId ?: $currentTenantId);
        }
        $costosDetalle = $costosQuery
            ->selectRaw('account_code, SUM(debit_amount) as total')
            ->groupBy('account_code')
            ->get();
        $costos = $costosDetalle->sum('total');

        // Gastos: todas las cuentas tipo 'Gasto' excepto "Costo de ventas" y "Compras"
        $gastosQuery = JournalEntryLine::whereHas('account', function($q) {
            $q->whereIn('type', ['expense', 'Gasto'])->whereNotIn('name', ['Costo de ventas', 'Compras']);
        })
        ->whereBetween('created_at', [$from, $to]);
        if ($applyTenantScope) {
            $gastosQuery->where('tenant_id', $filterTenantId ?: $currentTenantId);
        }
        $gastosDetalle = $gastosQuery
            ->selectRaw('account_code, SUM(debit_amount) as total')
            ->groupBy('account_code')
            ->get();
        $gastos = $gastosDetalle->sum('total');

        $utilidadBruta = $ingresos - $costos;
        $utilidadNeta = $utilidadBruta - $gastos;

        return view('reports.profit_and_loss', compact(
            'from', 'to',
            'ingresos', 'costos', 'gastos',
            'utilidadBruta', 'utilidadNeta',
            'ingresosDetalle', 'costosDetalle', 'gastosDetalle'
        ));
    }
} 