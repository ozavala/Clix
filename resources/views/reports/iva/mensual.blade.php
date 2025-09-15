@extends('layouts.app')

@section('title', 'Reporte Mensual de IVA')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Reporte Mensual de IVA</h3>
                    <div>
                        <a href="{{ route('iva.report.monthly', ['year' => $report['period']['year'] ?? now()->year, 'month' => $report['period']['month'] ?? now()->month, 'format' => 'pdf']) }}" 
                           class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-file-pdf"></i> Exportar PDF
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filtros -->
                    <form method="GET" action="{{ route('iva.report.monthly') }}" class="mb-4">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label for="year" class="form-label">Año</label>
                                <select name="year" id="year" class="form-select" required>
                                    @for($y = now()->year; $y >= 2020; $y--)
                                        <option value="{{ $y }}" {{ ($report['period']['year'] ?? '') == $y ? 'selected' : '' }}>{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="month" class="form-label">Mes</label>
                                <select name="month" id="month" class="form-select" required>
                                    @foreach(range(1, 12) as $m)
                                        <option value="{{ $m }}" {{ ($report['period']['month'] ?? '') == $m ? 'selected' : '' }}>
                                            {{ DateTime::createFromFormat('!m', $m)->format('F') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @if(isset($tenants) && $tenants->isNotEmpty())
                            <div class="col-md-4">
                                <label for="tenant_id" class="form-label">Tenant</label>
                                <select name="tenant_id" id="tenant_id" class="form-select">
                                    <option value="">Todos los Tenants</option>
                                    @foreach($tenants as $tenant)
                                        <option value="{{ $tenant->id }}" {{ $selectedTenantId == $tenant->id ? 'selected' : '' }}>
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

                    @if(isset($report))
                    <!-- Resumen del período -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <h6 class="alert-heading">Período del Reporte</h6>
                                <p class="mb-0">
                                    {{ $report['period']['start_formatted'] }} al {{ $report['period']['end_formatted'] }}
                                    @if(isset($selectedTenantId) && $selectedTenant = $tenants->firstWhere('id', $selectedTenantId))
                                        <br>Tenant: {{ $selectedTenant->name }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Resumen de IVA -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5 class="card-title">IVA Cobrado</h5>
                                    <h3 class="card-text">${{ number_format($report['summary']['total_tax_collected'], 2) }}</h3>
                                    <small>{{ $report['summary']['collection_count'] ?? 0 }} registros</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-warning text-dark">
                                <div class="card-body">
                                    <h5 class="card-title">IVA Pagado</h5>
                                    <h3 class="card-text">${{ number_format($report['summary']['total_tax_paid'], 2) }}</h3>
                                    <small>{{ $report['summary']['payment_count'] ?? 0 }} registros</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            @php
                                $balance = $report['summary']['net_tax_balance'];
                                $balanceClass = $balance >= 0 ? 'bg-info' : 'bg-danger';
                                $balanceText = $balance >= 0 ? 'A favor' : 'Por pagar';
                            @endphp
                            <div class="card {{ $balanceClass }} text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Saldo Neto de IVA</h5>
                                    <h3 class="card-text">${{ number_format(abs($balance), 2) }}</h3>
                                    <small>{{ $balanceText }}</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detalle por tasa de IVA -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Detalle por Tasa de IVA</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Tasa de IVA</th>
                                            <th class="text-end">Base Imponible</th>
                                            <th class="text-end">IVA Cobrado</th>
                                            <th class="text-end">IVA Pagado</th>
                                            <th class="text-end">Saldo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($report['details'] as $taxRateId => $items)
                                            @php
                                                $taxRate = $items->first();
                                                $collected = $items->where('record_type', 'collection')->sum('tax_amount');
                                                $paid = $items->where('record_type', 'payment')->sum('tax_amount');
                                                $balance = $collected - $paid;
                                            @endphp
                                            <tr>
                                                <td>{{ $taxRate->tax_rate_name }} ({{ $taxRate->tax_rate }}%)</td>
                                                <td class="text-end">${{ number_format($items->sum('taxable_amount'), 2) }}</td>
                                                <td class="text-end">${{ number_format($collected, 2) }}</td>
                                                <td class="text-end">${{ number_format($paid, 2) }}</td>
                                                <td class="text-end {{ $balance < 0 ? 'text-danger' : 'text-success' }}">
                                                    ${{ number_format(abs($balance), 2) }} {{ $balance < 0 ? '(Por pagar)' : '(A favor)' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Seleccione un año y mes para generar el reporte.
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
        $('select[name="year"], select[name="month"], select[name="tenant_id"]').on('change', function() {
            const year = $('select[name="year"]').val();
            const month = $('select[name="month"]').val();
            const tenantId = $('select[name="tenant_id"]').val();
            
            // Actualizar enlace de exportación PDF
            let pdfUrl = `/reportes/iva/mensual?year=${year}&month=${month}&format=pdf`;
            if (tenantId) {
                pdfUrl += `&tenant_id=${tenantId}`;
            }
            $('a[href*="format=pdf"]').attr('href', pdfUrl);
        });
    });
</script>
@endpush