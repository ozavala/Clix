@extends('layouts.app')

@section('title', 'Tax Balance Report')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Tax Balance Report</h3>
                    <div>
                        <a href="{{ route('reports.tax-balance.pdf') }}?start_date={{ $startDate }}&end_date={{ $endDate }}@if(isset($requestedTenantId))&tenant_id={{ $requestedTenantId }}@endif" 
                           class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </a>
                        <a href="{{ route('reports.tax-balance.excel') }}?start_date={{ $startDate }}&end_date={{ $endDate }}@if(isset($requestedTenantId))&tenant_id={{ $requestedTenantId }}@endif" 
                           class="btn btn-outline-success btn-sm">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Date and Tenant Filters -->
                    <form method="GET" action="{{ route('reports.tax-balance') }}" class="mb-4">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label for="start_date" class="form-label">Fecha Inicio</label>
                                <input type="date" name="start_date" id="start_date" class="form-control" 
                                       value="{{ request('start_date', now()->startOfMonth()->format('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-3">
                                <label for="end_date" class="form-label">Fecha Fin</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" 
                                       value="{{ request('end_date', now()->endOfMonth()->format('Y-m-d')) }}" required>
                            </div>
                            @if(auth()->user()->is_super_admin && $tenants->isNotEmpty())
                            <div class="col-md-4">
                                <label for="tenant_id" class="form-label">Tenant</label>
                                <select class="form-select" id="tenant_id" name="tenant_id">
                                    <option value="">Todos los Tenants</option>
                                    @foreach($tenants as $tenant)
                                        <option value="{{ $tenant->id }}" {{ $selectedTenantId == $tenant->id ? 'selected' : '' }}>
                                            {{ $tenant->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                            </div>
                        </div>
                    </form>

                    @if($selectedTenant)
                    <div class="alert alert-info mb-4">
                        <i class="fas fa-building"></i> 
                        Mostrando datos para el tenant: <strong>{{ $selectedTenant->name }}</strong>
                    </div>
                    @endif

                    @if(isset($report))
                    <!-- Report Period -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <h6 class="alert-heading">Report Period</h6>
                                <p class="mb-0">
                                    {{ $report['period']['start_formatted'] }} to {{ $report['period']['end_formatted'] }}
                                    @if(isset($requestedTenantId) && $requestedTenant = $tenants->firstWhere('id', $requestedTenantId))
                                        <br>Tenant: {{ $requestedTenant->name }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Resumen del período -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <h6 class="alert-heading">Report Period</h6>
                                <p class="mb-0">{{ $report['period']['start_formatted'] }} to {{ $report['period']['end_formatted'] }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Balance consolidado -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Tax Collected (Sales)</h5>
                                    <h3 class="card-text">${{ number_format($report['summary']['total_tax_collected'], 2) }}</h3>
                                    <small>{{ $report['summary']['total_invoices'] }} invoices</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-warning text-dark">
                                <div class="card-body">
                                    <h5 class="card-title">Tax Paid (Purchases)</h5>
                                    <h3 class="card-text">${{ number_format($report['summary']['total_tax_paid'], 2) }}</h3>
                                    <small>{{ $report['summary']['total_bills'] }} bills</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card {{ $report['summary']['balance_status'] === 'payable' ? 'bg-danger' : 'bg-info' }} text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Net Tax Balance</h5>
                                    <h3 class="card-text">${{ number_format($report['summary']['net_tax_balance'], 2) }}</h3>
                                    <small>{{ ucfirst($report['summary']['balance_status']) }}</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detalle por tasa de impuesto -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Sales Tax by Rate</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Tax Rate</th>
                                                    <th>Invoices</th>
                                                    <th>Taxable Amount</th>
                                                    <th>Tax Collected</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($report['sales_tax_by_rate'] as $taxRate)
                                                <tr>
                                                    <td>{{ $taxRate->tax_rate_name }} ({{ $taxRate->tax_rate_percentage }}%)</td>
                                                    <td>{{ $taxRate->invoice_count }}</td>
                                                    <td>${{ number_format($taxRate->total_taxable_amount, 2) }}</td>
                                                    <td>${{ number_format($taxRate->total_tax_collected, 2) }}</td>
                                                </tr>
                                                @empty
                                                <tr>
                                                    <td colspan="4" class="text-center">No sales tax data found</td>
                                                </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Purchase Tax by Rate</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Tax Rate</th>
                                                    <th>Bills</th>
                                                    <th>Taxable Amount</th>
                                                    <th>Tax Paid</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($report['purchase_tax_by_rate'] as $taxRate)
                                                <tr>
                                                    <td>{{ $taxRate->tax_rate_name }} ({{ $taxRate->tax_rate_percentage }}%)</td>
                                                    <td>{{ $taxRate->bill_count }}</td>
                                                    <td>${{ number_format($taxRate->total_taxable_amount, 2) }}</td>
                                                    <td>${{ number_format($taxRate->total_tax_paid, 2) }}</td>
                                                </tr>
                                                @empty
                                                <tr>
                                                    <td colspan="4" class="text-center">No purchase tax data found</td>
                                                </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top clientes y proveedores -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Top 10 Customers by Tax Paid</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Customer</th>
                                                    <th>Invoices</th>
                                                    <th>Tax Collected</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($report['top_customers_by_tax'] as $customer)
                                                <tr>
                                                    <td>
                                                        {{ $customer->customer_name ?: $customer->first_name . ' ' . $customer->last_name }}
                                                    </td>
                                                    <td>{{ $customer->invoice_count }}</td>
                                                    <td>${{ number_format($customer->total_tax_collected, 2) }}</td>
                                                </tr>
                                                @empty
                                                <tr>
                                                    <td colspan="3" class="text-center">No customer data found</td>
                                                </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Top 10 Suppliers by Tax Paid</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Supplier</th>
                                                    <th>Bills</th>
                                                    <th>Tax Paid</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($report['top_suppliers_by_tax'] as $supplier)
                                                <tr>
                                                    <td>
                                                        {{ $supplier->supplier_name }}
                                                        @if($supplier->contact_person)
                                                            <br><small class="text-muted">{{ $supplier->contact_person }}</small>
                                                        @endif
                                                    </td>
                                                    <td>{{ $supplier->bill_count }}</td>
                                                    <td>${{ number_format($supplier->total_tax_paid, 2) }}</td>
                                                </tr>
                                                @empty
                                                <tr>
                                                    <td colspan="3" class="text-center">No supplier data found</td>
                                                </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
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
    document.addEventListener('DOMContentLoaded', function() {
        // Update export links when filters change
        function updateExportLinks() {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            const tenantId = document.getElementById('tenant_id') ? document.getElementById('tenant_id').value : '';
            
            // Update PDF export link
            let pdfUrl = '{{ route("reports.tax-balance.export-pdf") }}' + 
                '?start_date=' + encodeURIComponent(startDate) + 
                '&end_date=' + encodeURIComponent(endDate);
                
            if (tenantId) {
                pdfUrl += '&tenant_id=' + encodeURIComponent(tenantId);
            }
            
            document.querySelector('a[href*="export-pdf"]').setAttribute('href', pdfUrl);
            
            // Update Excel export link
            let excelUrl = '{{ route("reports.tax-balance.export-excel") }}' + 
                '?start_date=' + encodeURIComponent(startDate) + 
                '&end_date=' + encodeURIComponent(endDate);
                
            if (tenantId) {
                excelUrl += '&tenant_id=' + encodeURIComponent(tenantId);
            }
            
            document.querySelector('a[href*="export-excel"]').setAttribute('href', excelUrl);
        }
        
        // Add event listeners for filter changes
        document.getElementById('start_date').addEventListener('change', updateExportLinks);
        document.getElementById('end_date').addEventListener('change', updateExportLinks);
        
        const tenantSelect = document.getElementById('tenant_id');
        if (tenantSelect) {
            tenantSelect.addEventListener('change', updateExportLinks);
        }
        
        // Initialize export links
        updateExportLinks();
    });
</script>
@endpush