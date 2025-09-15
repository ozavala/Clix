<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Reporte Mensual de IVA - {{ $report['period']['month'] }}/{{ $report['period']['year'] }}</title>
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
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte Mensual de IVA</h1>
        <p>Período: {{ $report['period']['start_formatted'] }} al {{ $report['period']['end_formatted'] }}</p>
        @if(isset($selectedTenant) && $selectedTenant)
        <p>Tenant: {{ $selectedTenant->name }}</p>
        @endif
        <p>Generado el: {{ now()->format('d/m/Y H:i:s') }}</p>
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
                <div>Saldo Neto de IVA</div>
                <div style="font-size: 14px; font-weight: bold;">${{ number_format(abs($balance), 2) }}</div>
                <div style="font-size: 9px;">{{ $balanceText }}</div>
            </div>
        </div>
    </div>

    <h3 style="font-size: 14px; margin-bottom: 10px;">Detalle por Tasa de IVA</h3>
    <table class="table">
        <thead>
            <tr>
                <th style="width: 30%;">Tasa de IVA</th>
                <th class="text-right" style="width: 17.5%;">Base Imponible</th>
                <th class="text-right" style="width: 17.5%;">IVA Cobrado</th>
                <th class="text-right" style="width: 17.5%;">IVA Pagado</th>
                <th class="text-right" style="width: 17.5%;">Saldo</th>
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
                        ${{ number_format(abs($balance), 2) }} {{ $balance < 0 ? '(Por pagar)' : '(A favor)' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Reporte generado por {{ config('app.name') }} - {{ config('app.url') }}</p>
    </div>
</body>
</html>