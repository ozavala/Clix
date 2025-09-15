@extends('layouts.app')

@section('title', 'Orders')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Orders</h1>
        <div class="d-flex align-items-center gap-2">
            @isset($tenants)
            <form action="{{ route('orders.index') }}" method="GET" class="d-flex align-items-center me-2">
                <label for="tenant_id" class="me-2 mb-0">Tenant:</label>
                <select name="tenant_id" id="tenant_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All</option>
                    @foreach($tenants as $t)
                        <option value="{{ $t->id }}" {{ (request('tenant_id') ?? ($requestedTenantId ?? '')) == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
            </form>
            @endisset
            <a href="{{ route('orders.create') }}" class="btn btn-primary">Add New Order</a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="mb-3">
        <form action="{{ route('orders.index') }}" method="GET">
            <div class="row g-2">
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control" placeholder="Search by order #, customer..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="status_filter" class="form-select">
                        <option value="">All Statuses</option>
                        @foreach($statuses as $key => $value)
                            <option value="{{ $key }}" {{ request('status_filter') == $key ? 'selected' : '' }}>{{ $value }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex">
                    <button type="submit" class="btn btn-primary flex-grow-1">Filter</button>
                    <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary ms-2" title="Clear Filters"><i class="bi bi-x-lg"></i> Clear</a>
                </div>
            </div>
        </form>
    </div>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Order #</th>
                <th>Customer</th>
                <th>Status</th>
                <th>Total</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($orders as $order)
                <tr>
                    <td>{{ $order->order_id }}</td>
                    <td><a href="{{ route('orders.show', $order->order_id) }}">{{ $order->order_number ?: ('Order #'.$order->order_id) }}</a></td>
                    <td>{{ $order->customer->full_name ?? 'N/A' }}</td>
                    <td>
                        @php
                            $statusClass = match($order->status) {
                                'Completed' => 'bg-success',
                                'Shipped', 'Processing' => 'bg-info text-dark',
                                'Pending' => 'bg-warning text-dark',
                                'Cancelled' => 'bg-danger',
                                default => 'bg-secondary'
                            };
                        @endphp
                        <span class="badge {{ $statusClass }}">{{ $order->status }}</span>
                    </td>
                    <td>${{ number_format($order->total_amount, 2) }}</td>
                    <td>{{ $order->order_date->format('Y-m-d') }}</td>
                    <td>
                        <a href="{{ route('orders.edit', $order->order_id) }}" class="btn btn-warning btn-sm">Edit</a>
                        <form action="{{ route('orders.destroy', $order->order_id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">No orders found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="d-flex justify-content-center">
        {{ $orders->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection