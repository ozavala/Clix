@extends('layouts.app')

@section('title', 'Purchase Orders')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Purchase Orders</h1>
        <div class="d-flex align-items-center gap-2">
            @isset($tenants)
            <form action="{{ route('purchase-orders.index') }}" method="GET" class="d-flex align-items-center me-2">
                <label for="tenant_id" class="me-2 mb-0">Tenant:</label>
                <select name="tenant_id" id="tenant_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All</option>
                    @foreach($tenants as $t)
                        <option value="{{ $t->id }}" {{ (request('tenant_id') ?? ($requestedTenantId ?? '')) == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
            </form>
            @endisset
            <a href="{{ route('purchase-orders.create') }}" class="btn btn-primary">Add Purchase Order</a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="mb-3">
        <form action="{{ route('purchase-orders.index') }}" method="GET">
            <div class="row g-2">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control me-2" placeholder="Search purchase orders..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="type_filter" class="form-select">
                        <option value="">All Types</option>
                        @foreach($types as $key => $value)
                            <option value="{{ $key }}" {{ request('type_filter') == $key ? 'selected' : '' }}>{{ $value }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status_filter" class="form-select">
                        <option value="">All Statuses</option>
                        @foreach($statuses as $key => $value)
                            <option value="{{ $key }}" {{ request('status_filter') == $key ? 'selected' : '' }}>{{ $value }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-outline-primary">Search</button>
                    <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-secondary ms-2">Clear</a>
                </div>
            </div>
        </form>
    </div>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Order #</th>
                <th>Supplier</th>
                <th>Type</th>
                <th>Status</th>
                <th>Total</th>
                <th>Paid</th>
                <th>Due</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($purchaseOrders as $purchaseOrder)
                <tr>
                    <td>{{ $purchaseOrder->purchase_order_id }}</td>
                    <td><a href="{{ route('purchase-orders.show', $purchaseOrder->purchase_order_id) }}">{{ $purchaseOrder->purchase_order_number ?: ('PO #'.$purchaseOrder->purchase_order_id) }}</a></td>
                    <td>{{ $purchaseOrder->supplier->name ?? 'N/A' }}</td>
                    <td>{{ $purchaseOrder->type ?: 'N/A' }}</td>
                    <td>
                         @php
                            $statusClass = match($purchaseOrder->status) {
                                'Completed', 'Received' => 'bg-success',
                                'Sent', 'Confirmed' => 'bg-info text-dark',
                                'Partially Received' => 'bg-warning text-dark',
                                'Cancelled' => 'bg-danger',
                                default => 'bg-secondary'
                            };
                        @endphp
                        <span class="badge {{ $statusClass }}">{{ $purchaseOrder->status }}</span>
                    </td>
                    <td class="text-end">${{ number_format($purchaseOrder->total_amount, 2) }}</td>
                    <td class="text-end">${{ number_format($purchaseOrder->amount_paid, 2) }}</td>
                    <td class="text-end">${{ number_format($purchaseOrder->amount_due, 2) }}</td>
                    <td>{{ $purchaseOrder->order_date->format('Y-m-d') }}</td>
                    <td>
                        <a href="{{ route('purchase-orders.edit', $purchaseOrder->purchase_order_id) }}" class="btn btn-warning btn-sm">Edit</a>
                        <form action="{{ route('purchase-orders.destroy', $purchaseOrder->purchase_order_id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure? This action cannot be undone.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center">No purchase orders found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="d-flex justify-content-center">
        {{ $purchaseOrders->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection