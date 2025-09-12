@extends('layouts.app')

@section('title', 'Order Details: ' . ($order->order_number ?? $order->order_id))

@section('content')
<div class="container">
   
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1>Order <span class="text-muted">#{{ $order->order_number ?? $order->order_id }}</span></h1>
            @if($order->customer)
                <p class="lead">For customer: <a href="{{ route('customers.show', $order->customer->customer_id) }}">{{ $order->customer->full_name }}</a></p>
            @else
                <p class="lead">For customer: <span class="text-muted">N/A</span></p>
            @endif
        </div>
        <div>
            <a href="{{ route('orders.index') }}" class="btn btn-secondary">Back to Orders</a>
            <a href="{{ route('orders.edit', $order->order_id) }}" class="btn btn-warning">Edit</a>
        </div>
    </div>

    
    {{-- Session Messages --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

            
    {{-- Action Buttons --}}
    <div class="card mb-4">
        <div class="card-header">
            Actions
        </div>
        <div class="card-body d-flex gap-2">
            @php
                $canInvoice = in_array($order->status, ['Processing', 'Shipped', 'Delivered', 'Completed']);
            @endphp

            @if($canInvoice && $order->invoices->isEmpty())
                <a href="{{ route('invoices.create', ['order_id' => $order->order_id]) }}" class="btn btn-primary">Create Invoice</a>
            @elseif($order->invoices->isNotEmpty())
                @foreach($order->invoices as $invoice)
                    <a href="{{ route('invoices.show', $invoice->invoice_id) }}" class="btn btn-info">View Invoice #{{ $invoice->invoice_number }}</a>
                @endforeach
            @else
                 <p class="mb-0">No actions available for status: <strong>{{ $order->status }}</strong></p>
            @endif
        </div>
    </div>

            
    {{-- Details --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Order Details</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    @if($order->customer)
                        <p><strong>Customer:</strong> <a href="{{ route('customers.show', $order->customer->customer_id) }}">{{ $order->customer->full_name }}</a></p>
                    @else
                        <p><strong>Customer:</strong> <span class="text-muted">N/A</span></p>
                    @endif
                    @if($order->quotation)
                    <p><strong>From Quotation:</strong> <a href="{{ route('quotations.show', $order->quotation->quotation_id) }}">{{ $order->quotation->subject }}</a></p>
                    @endif
                    <p><strong>Status:</strong> <span class="badge bg-primary">{{ $order->status }}</span></p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p><strong>Order Date:</strong> {{ $order->order_date->format('Y-m-d') }}</p>
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
                    @foreach($order->items as $item)
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
                        <td class="text-end">${{ number_format($order->subtotal, 2) }}</td>
                    </tr>
                    @if($order->discount_amount > 0)
                    <tr>
                        <td colspan="3" class="text-end"><strong>Discount</strong></td>
                        <td class="text-end text-danger">- ${{ number_format($order->discount_amount, 2) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td colspan="3" class="text-end"><strong>Tax ({{ $order->tax_percentage }}%)</strong></td>
                        <td class="text-end">${{ number_format($order->tax_amount, 2) }}</td>
                    </tr>
                    <tr class="fw-bold">
                        <td colspan="3" class="text-end"><strong>Total</strong></td>
                        <td class="text-end">${{ number_format($order->total_amount, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Payments Section --}}
    @include('partials._payments', ['model' => $order])
</div>
@endsection