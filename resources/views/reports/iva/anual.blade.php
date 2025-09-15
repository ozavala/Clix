@extends('layouts.app')

@section('title', 'Reporte Anual de IVA')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Reporte Anual de IVA - {{ $year ?? '' }}</h3>
                    <div>
                        @if(isset($year))
                        <a href="{{ route('iva.report.annual', ['year' => $year, 'format' => 'pdf', 'tenant_id' => $selectedTenantId]) }}" 
                           class="btn btn-outline-secondary btn-sm" id="exportPdf">
                            <i class="fas fa-file-pdf"></i> Exportar PDF
                        </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filtros -->
                    <form method="GET" action="{{ route('iva.report.annual') }}" class="mb-4">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label for="year" class="form-label">Año</label>
                                <select name="year" id="year" class="form-select" required>
                                    @for($y = now()->year; $y >= 2020; $y--)
                                        <option value="{{ $y }}" {{ ($year ?? '') == $y ? 'selected' : '' }}>{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                            @if(isset($tenants) && $tenants->isNotEmpty())
                            <div class="col-md-6">
                                <label for="tenant_id" class="form-label">Tenant</label>
                                <select name="tenant_id" id="tenant_id" class="form-select">
                                    <option value="">Todos los Tenants</option>
                                    @foreach($tenants as $tenant)
                                        <option value="{{ $tenant->id }}" {{ ($selectedTenantId ?? '') == $tenant->id ? 'selected' : '' }}>
                                            {{ $tenant->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Generar
                                </button>
                            </div>
                        </div>
                    </form>

                    @if(isset($yearly_summary))
                    <!-- Resumen del año -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <h6 class="alert-heading">Resumen Anual {{ $year }}</h6>
                                @if(isset($selectedTenantId) && $selectedTenant = $tenants->firstWhere('id', $selectedTenantId))
                                    <p class="mb-0">Tenant: {{ $selectedTenant->name }}</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Resumen de IVA Anual -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total IVA Cobrado</h5>
                                    <h3 class="card-text">${{ number_format($yearly_summary['total_tax_collected'], 2) }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-warning text-dark">
                                <div class="card-body">
                                    <h5 class="card-title">Total IVA Pagado</h5>
                                    <h3 class="card-text">${{ number_format($yearly_summary['total_tax_paid'], 2) }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            @php
                                $balance = $yearly_summary['net_tax_balance'];
                                $balanceClass = $balance >= 0 ? 'bg-info' : 'bg-danger';
                                $balanceText = $balance >= 0 ? 'A favor' : 'Por pagar';
                            @endphp
                            <div class="card {{ $balanceClass }} text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Saldo Neto Anual</h5>
                                    <h3 class="card-text">${{ number_format(abs($balance), 2) }}</h3>
                                    <small>{{ $balanceText }}</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Resumen Mensual -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Resumen Mensual</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Mes</th>
                                            <th class="text-end">IVA Cobrado</th>
                                            <th class="text-end">IVA Pagado</th>
                                            <th class="text-end">Saldo</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($monthly_reports as $month => $report)
                                            @php
                                                $monthName = DateTime::createFromFormat('!m', $month)->format('F');
                                                $balance = $report['summary']['net_tax_balance'];
                                                $balanceClass = $balance < 0 ? 'text-danger' : 'text-success';
                                            @endphp
                                            <tr>
                                                <td>{{ ucfirst($monthName) }}</td>
                                                <td class="text-end">${{ number_format($report['summary']['total_tax_collected'], 2) }}</td>
                                                <td class="text-end">${{ number_format($report['summary']['total_tax_paid'], 2) }}</td>
                                                <td class="text-end {{ $balanceClass }}">
                                                    ${{ number_format(abs($balance), 2) }}
                                                </td>
                                                <td class="text-center">
                                                    <a href="{{ route('iva.report.monthly', [
                                                        'year' => $year, 
                                                        'month' => $month,
                                                        'tenant_id' => $selectedTenantId
                                                    ]) }}" 
                                                       class="btn btn-sm btn-outline-primary"
                                                       title="Ver Detalle">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-active">
                                            <th>Total Anual</th>
                                            <th class="text-end">${{ number_format($yearly_summary['total_tax_collected'], 2) }}</th>
                                            <th class="text-end">${{ number_format($yearly_summary['total_tax_paid'], 2) }}</th>
                                            <th class="text-end {{ $yearly_summary['net_tax_balance'] < 0 ? 'text-danger' : 'text-success' }}">
                                                ${{ number_format(abs($yearly_summary['net_tax_balance']), 2) }}
                                            </th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Seleccione un año para generar el reporte anual.
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Actualizar enlaces de exportación cuando cambien los filtros
        $('select[name="year"], select[name="tenant_id"]').on('change', function() {
            const year = $('select[name="year"]').val();
            const tenantId = $('select[name="tenant_id"]').val();
            
            // Actualizar enlace de exportación PDF
            let pdfUrl = `/reportes/iva/anual?year=${year}&format=pdf`;
            if (tenantId) {
                pdfUrl += `&tenant_id=${tenantId}`;
            }
            $('#exportPdf').attr('href', pdfUrl);
        });
    });
</script>
@endpush