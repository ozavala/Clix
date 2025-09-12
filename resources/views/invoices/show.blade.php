@extends('layouts.app')

@section('title', __('invoices.details') . ': ' . $invoice->invoice_number)

@section('content')
<div class="container">
   
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1>Invoice <span class="text-muted">#{{ $invoice->invoice_number }}</span></h1>
            @if($invoice->customer)
                <p class="lead">For: <a href="{{ route('customers.show', $invoice->customer->customer_id) }}">{{ $invoice->customer->full_name }}</a></p>
            @else
                <p class="lead">For: <span class="text-muted">Unknown customer</span></p>
            @endif
        </div>
        <div>
            <a href="{{ route('invoices.index') }}" class="btn btn-secondary">Back to Invoices</a>
            <a href="{{ route('invoices.edit', $invoice->invoice_id) }}" class="btn btn-warning">Edit Invoice</a>
            <a href="{{ route('invoices.pdf', $invoice->invoice_id) }}" class="btn btn-info" target="_blank">Download PDF</a>
            @if($invoice->status === 'Overdue' && $invoice->amount_due > 0)
                <form action="{{ route('invoices.sendReminder', $invoice->invoice_id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-danger">Send Reminder</button>
                </form>
            @endif
        </div>
    </div>

    
    {{-- Session Messages --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

            
    {{-- Details --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Invoice Details</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    @if($invoice->customer)
                        <p><strong>Customer:</strong> <a href="{{ route('customers.show', $invoice->customer->customer_id) }}">{{ $invoice->customer->full_name }}</a></p>
                    @else
                        <p><strong>Customer:</strong> <span class="text-muted">N/A</span></p>
                    @endif
                    @if($invoice->order)
                        <p><strong>Order:</strong> <a href="{{ route('orders.show', $invoice->order->order_id) }}">#{{ $invoice->order->order_number }}</a></p>
                    @endif
                    @if($invoice->quotation)
                        <p><strong>Quotation:</strong> <a href="{{ route('quotations.show', $invoice->quotation->quotation_id) }}">{{ $invoice->quotation->subject }}</a></p>
                    @endif
                    <p><strong>Status:</strong> <span class="badge {{ match($invoice->status) {'Paid' => 'bg-success', 'Partially Paid' => 'bg-warning text-dark', 'Overdue' => 'bg-danger', default => 'bg-secondary'} }}">{{ $invoice->status }}</span></p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p><strong>Invoice Date:</strong> {{ $invoice->invoice_date->format('Y-m-d') }}</p>
                    <p><strong>Due Date:</strong> {{ $invoice->due_date->format('Y-m-d') }}</p>
                </div>
            </div>
        </div>
    </div>

        
    {{-- Items and Totals --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Items</h5>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="text-end">Quantity</th>
                        <th class="text-end">Unit Price</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->item_name }}</strong><br>
                                <small class="text-muted">{{ $item->item_description }}</small>
                            </td>
                            <td class="text-end">{{ $item->quantity }}</td>
                            <td class="text-end">${{ number_format($item->unit_price, 2) }}</td>
                            <td class="text-end">${{ number_format($item->item_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end"><strong>Subtotal</strong></td>
                        <td class="text-end">${{ number_format($invoice->subtotal, 2) }}</td>
                    </tr>
                    @if($invoice->discount_amount > 0)
                    <tr>
                        <td colspan="3" class="text-end"><strong>Discount</strong></td>
                        <td class="text-end text-danger">- ${{ number_format($invoice->discount_amount, 2) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td colspan="3" class="text-end"><strong>Tax ({{ $invoice->tax_percentage }}%)</strong></td>
                        <td class="text-end">${{ number_format($invoice->tax_amount, 2) }}</td>
                    </tr>
                    <tr class="fw-bold">
                        <td colspan="3" class="text-end"><strong>Total</strong></td>
                        <td class="text-end">${{ number_format($invoice->total_amount, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Payments Section --}}
    @include('partials._payments', ['model' => $invoice])

    {{-- Notes & Terms --}}
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Notes</div>
                <div class="card-body">
                    <p>{{ $invoice->notes ?: 'No notes.' }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Terms & Conditions</div>
                <div class="card-body">
                    <p>{{ $invoice->terms_and_conditions ?: 'No terms.' }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
