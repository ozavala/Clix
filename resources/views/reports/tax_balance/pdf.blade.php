<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tax Balance Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        .header p {
            margin: 5px 0;
            font-size: 14px;
        }
        .summary-section {
            margin-bottom: 30px;
        }
        .summary-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .summary-item {
            display: table-cell;
            width: 33.33%;
            padding: 10px;
            text-align: center;
            border: 1px solid #ddd;
        }
        .summary-item h3 {
            margin: 0 0 5px 0;
            font-size: 16px;
        }
        .summary-item .amount {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .summary-item .label {
            font-size: 12px;
            color: #666;
        }
        .tax-collected {
            background-color: #d4edda;
        }
        .tax-paid {
            background-color: #fff3cd;
        }
        .net-balance {
            background-color: #f8d7da;
        }
        .net-balance.refundable {
            background-color: #d1ecf1;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin: 20px 0 10px 0;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .page-break {
            page-break-before: always;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .text-bold {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Tax Balance Report</h1>
        <p>Period: {{ $report['period']['start_formatted'] }} to {{ $report['period']['end_formatted'] }}</p>
        @if(isset($tenant))
        <p>Tenant: {{ $tenant->name }}</p>
        @endif
        <p>Generated on: {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>

    <!-- Summary Section -->
    <div class="summary-section">
        <div class="section-title">Tax Balance Summary</div>
        <div class="summary-grid">
            <div class="summary-item tax-collected">
                <h3>Tax Collected (Sales)</h3>
                <div class="amount">${{ number_format($report['summary']['total_tax_collected'], 2) }}</div>
                <div class="label">{{ $report['summary']['total_invoices'] }} invoices</div>
            </div>
            <div class="summary-item tax-paid">
                <h3>Tax Paid (Purchases)</h3>
                <div class="amount">${{ number_format($report['summary']['total_tax_paid'], 2) }}</div>
                <div class="label">{{ $report['summary']['total_bills'] }} bills</div>
            </div>
            <div class="summary-item net-balance {{ $report['summary']['balance_status'] === 'refundable' ? 'refundable' : '' }}">
                <h3>Net Tax Balance</h3>
                <div class="amount">${{ number_format($report['summary']['net_tax_balance'], 2) }}</div>
                <div class="label">{{ ucfirst($report['summary']['balance_status']) }}</div>
            </div>
        </div>
    </div>

    <!-- Sales Tax by Rate -->
    <div class="section-title">Sales Tax by Rate</div>
    <table>
        <thead>
            <tr>
                <th>Tax Rate</th>
                <th class="text-center">Invoices</th>
                <th class="text-right">Taxable Amount</th>
                <th class="text-right">Tax Collected</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['sales_tax_by_rate'] as $taxRate)
            <tr>
                <td>{{ $taxRate->tax_rate_name }} ({{ $taxRate->tax_rate_percentage }}%)</td>
                <td class="text-center">{{ $taxRate->invoice_count }}</td>
                <td class="text-right">${{ number_format($taxRate->total_taxable_amount, 2) }}</td>
                <td class="text-right">${{ number_format($taxRate->total_tax_collected, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="text-center">No sales tax data found</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Purchase Tax by Rate -->
    <div class="section-title">Purchase Tax by Rate</div>
    <table>
        <thead>
            <tr>
                <th>Tax Rate</th>
                <th class="text-center">Bills</th>
                <th class="text-right">Taxable Amount</th>
                <th class="text-right">Tax Paid</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['purchase_tax_by_rate'] as $taxRate)
            <tr>
                <td>{{ $taxRate->tax_rate_name }} ({{ $taxRate->tax_rate_percentage }}%)</td>
                <td class="text-center">{{ $taxRate->bill_count }}</td>
                <td class="text-right">${{ number_format($taxRate->total_taxable_amount, 2) }}</td>
                <td class="text-right">${{ number_format($taxRate->total_tax_paid, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="text-center">No purchase tax data found</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="page-break"></div>

    <!-- Top Customers by Tax -->
    <div class="section-title">Top 10 Customers by Tax Collected</div>
    <table>
        <thead>
            <tr>
                <th>Customer</th>
                <th class="text-center">Invoices</th>
                <th class="text-right">Tax Collected</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['top_customers_by_tax'] as $customer)
            <tr>
                <td>{{ $customer->customer_name ?: $customer->first_name . ' ' . $customer->last_name }}</td>
                <td class="text-center">{{ $customer->invoice_count }}</td>
                <td class="text-right">${{ number_format($customer->total_tax_collected, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="3" class="text-center">No customer data found</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Top Suppliers by Tax -->
    <div class="section-title">Top 10 Suppliers by Tax Paid</div>
    <table>
        <thead>
            <tr>
                <th>Supplier</th>
                <th class="text-center">Bills</th>
                <th class="text-right">Tax Paid</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['top_suppliers_by_tax'] as $supplier)
            <tr>
                <td>
                    {{ $supplier->supplier_name }}
                    @if($supplier->contact_person)
                        <br><small>{{ $supplier->contact_person }}</small>
                    @endif
                </td>
                <td class="text-center">{{ $supplier->bill_count }}</td>
                <td class="text-right">${{ number_format($supplier->total_tax_paid, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="3" class="text-center">No supplier data found</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Footer -->
    <div style="margin-top: 50px; text-align: center; font-size: 10px; color: #666;">
        <p>This report was generated automatically by the CRM/ERP system.</p>
        <p>For questions about this report, please contact your system administrator.</p>
    </div>
</body>
</html>