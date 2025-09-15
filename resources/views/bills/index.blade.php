@extends('layouts.app')

@section('title', __('bills.Bills'))

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Bills</h1>
        <div class="d-flex align-items-center gap-2">
            @isset($tenants)
            <form action="{{ route('bills.index') }}" method="GET" class="d-flex align-items-center me-2">
                <label for="tenant_id" class="me-2 mb-0">Tenant:</label>
                <select name="tenant_id" id="tenant_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All</option>
                    @foreach($tenants as $t)
                        <option value="{{ $t->id }}" {{ (request('tenant_id') ?? ($requestedTenantId ?? '')) == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
            </form>
            @endisset
            <a href="{{ route('bills.create') }}" class="btn btn-primary">Add Bill</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Supplier</th>
                            <th>Bill Date</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Amount Due</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bills as $bill)
                            <tr>
                                <td><a href="{{ route('bills.show', $bill) }}">{{ $bill->bill_number }}</a></td>
                                <td><a href="{{ route('suppliers.show', $bill->supplier) }}">{{ $bill->supplier->name }}</a></td>
                                <td>{{ $bill->bill_date->format('Y-m-d') }}</td>
                                <td>{{ $bill->due_date->format('Y-m-d') }}</td>
                                <td>
                                    @php
                                        $statusClass = match($bill->status) {
                                            'Paid' => 'bg-success',
                                            'Awaiting Payment' => 'bg-info text-dark',
                                            'Partially Paid' => 'bg-warning text-dark',
                                            'Cancelled' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                    @endphp
                                    <span class="badge {{ $statusClass }}">{{ $bill->status }}</span>
                                </td>
                                <td class="text-end">${{ number_format($bill->total_amount, 2) }}</td>
                                <td class="text-end">${{ number_format($bill->amount_due, 2) }}</td>
                                <td>
                                    <a href="{{ route('bills.show', $bill) }}" class="btn btn-sm btn-info">View</a>
                                    <a href="{{ route('bills.edit', $bill->bill_id) }}" class="btn btn-warning btn-sm">Edit</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">No bills found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center">
                {{ $bills->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>
@endsection