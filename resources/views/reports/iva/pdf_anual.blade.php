<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Reporte Anual de IVA - {{ $year }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { font-size: 18px; margin-bottom: 5px; }
        .header p { margin: 0; font-size: 12px; }
        .summary { margin-bottom: 20px; }
        .summary-row { display: flex; margin-bottom: 15px; }
        .summary-box { 
            flex: 1; 
            padding: 10px; 
            margin: 0 5px; 
            border-radius: 4px; 
            color: white;
            text-align: center;
        }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #f2f2f2; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer { margin-top: 30px; font-size: 9px; text-align: center; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte Anual de IVA</h1>
        <p>Año: {{ $year }}</p>
        @if(isset($selectedTenant) && $selectedTenant)
        <p>Tenant: {{ $selectedTenant->name }}</p>
        @endif
        <p>Generado el: {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>

    <!-- Resumen Anual -->
    <div class="summary">
        <h3 style="font-size: 14px; margin-bottom: 10px;">Resumen Anual</h3>
        <div class="summary-row">
            <div class="summary-box" style="background-color: #28a745;">
                <div>Total IVA Cobrado</div>
                <div style="font-size: 14px; font-weight: bold;">${{ number_format($yearly_summary['total_tax_collected'], 2) }}</div>
            </div>
            <div class="summary-box" style="background-color: #ffc107; color: #212529;">
                <div>Total IVA Pagado</div>
                <div style="font-size: 14px; font-weight: bold;">${{ number_format($yearly_summary['total_tax_paid'], 2) }}</div>
            </div>
            @php
                $balance = $yearly_summary['net_tax_balance'];
                $balanceColor = $balance >= 0 ? '#17a2b8' : '#dc3545';
                $balanceText = $balance >= 0 ? 'A favor' : 'Por pagar';
            @endphp
            <div class="summary-box" style="background-color: {{ $balanceColor }};">
                <div>Saldo Neto Anual</div>
                <div style="font-size: 14px; font-weight: bold;">${{ number_format(abs($balance), 2) }}</div>
                <div style="font-size: 9px;">{{ $balanceText }}</div>
            </div>
        </div>
    </div>

    <!-- Resumen Mensual -->
    <h3 style="font-size: 14px; margin-bottom: 10px;">Resumen Mensual</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Mes</th>
                <th class="text-right">IVA Cobrado</th>
                <th class="text-right">IVA Pagado</th>
                <th class="text-right">Saldo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($monthly_reports as $month => $report)
                @php
                    $monthName = DateTime::createFromFormat('!m', $month)->format('F');
                    $balance = $report['summary']['net_tax_balance'];
                    $balanceClass = $balance < 0 ? 'color: #dc3545;' : 'color: #28a745;';
                @endphp
                <tr>
                    <td>{{ ucfirst($monthName) }}</td>
                    <td class="text-right">${{ number_format($report['summary']['total_tax_collected'], 2) }}</td>
                    <td class="text-right">${{ number_format($report['summary']['total_tax_paid'], 2) }}</td>
                    <td class="text-right" style="{{ $balanceClass }}">
                        ${{ number_format(abs($balance), 2) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color: #f8f9fa; font-weight: bold;">
                <td>Total Anual</td>
                <td class="text-right">${{ number_format($yearly_summary['total_tax_collected'], 2) }}</td>
                <td class="text-right">${{ number_format($yearly_summary['total_tax_paid'], 2) }}</td>
                <td class="text-right" style="{{ $yearly_summary['net_tax_balance'] < 0 ? 'color: #dc3545;' : 'color: #28a745;' }}">
                    ${{ number_format(abs($yearly_summary['net_tax_balance']), 2) }}
                </td>
            </tr>
        </tfoot>
    </table>

    <!-- Detalle por Mes -->
    @foreach($monthly_reports as $month => $report)
        <div class="page-break"></div>
        <div class="header">
            <h2>Detalle de {{ ucfirst(DateTime::createFromFormat('!m', $month)->format('F')) }} {{ $year }}</h2>
            @if(isset($selectedTenant) && $selectedTenant)
            <p>Tenant: {{ $selectedTenant->name }}</p>
            @endif
        </div>

        <div class="summary">
            <div class="summary-row">
                <div class="summary-box" style="background-color: #28a745;">
                    <div>IVA Cobrado</div>
                    <div style="font-size: 14px; font-weight: bold;">${{ number_format($report['summary']['total_tax_collected'], 2) }}</div>
                    <div style="font-size: 9px;">{{ $report['summary']['collection_count'] ?? 0 }} registros</div>
                </div>
                <div class="summary-box" style="background-color: #ffc107; color: #212529;">
                    <div>IVA Pagado</div>
                    <div style="font-size: 14px; font-weight: bold;">${{ number_format($report['summary']['total_tax_paid'], 2) }}</div>
                    <div style="font-size: 9px;">{{ $report['summary']['payment_count'] ?? 0 }} registros</div>
                </div>
                @php
                    $balance = $report['summary']['net_tax_balance'];
                    $balanceColor = $balance >= 0 ? '#17a2b8' : '#dc3545';
                    $balanceText = $balance >= 0 ? 'A favor' : 'Por pagar';
                @endphp
                <div class="summary-box" style="background-color: {{ $balanceColor }};">
                    <div>Saldo Neto</div>
                    <div style="font-size: 14px; font-weight: bold;">${{ number_format(abs($balance), 2) }}</div>
                    <div style="font-size: 9px;">{{ $balanceText }}</div>
                </div>
            </div>
        </div>

        <h3 style="font-size: 14px; margin-bottom: 10px;">Detalle por Tasa de IVA</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Tasa de IVA</th>
                    <th class="text-right">Base Imponible</th>
                    <th class="text-right">IVA Cobrado</th>
                    <th class="text-right">IVA Pagado</th>
                    <th class="text-right">Saldo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report['details'] as $taxRateId => $items)
                    @php
                        $taxRate = $items->first();
                        $collected = $items->where('record_type', 'collection')->sum('tax_amount');
                        $paid = $items->where('record_type', 'payment')->sum('tax_amount');
                        $balance = $collected - $paid;
                        $balanceClass = $balance < 0 ? 'color: #dc3545;' : 'color: #28a745;';
                    @endphp
                    <tr>
                        <td>{{ $taxRate->tax_rate_name }} ({{ $taxRate->tax_rate }}%)</td>
                        <td class="text-right">${{ number_format($items->sum('taxable_amount'), 2) }}</td>
                        <td class="text-right">${{ number_format($collected, 2) }}</td>
                        <td class="text-right">${{ number_format($paid, 2) }}</td>
                        <td class="text-right" style="{{ $balanceClass }}">
                            ${{ number_format(abs($balance), 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    <div class="footer">
        <p>Reporte generado por {{ config('app.name') }} - {{ config('app.url') }}</p>
    </div>
</body>
</html>