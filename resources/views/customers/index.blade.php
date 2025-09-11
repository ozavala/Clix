@extends('layouts.app')

@section('title', __('Customers'))

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>{{ __('Customers') }}</h1>
        <a href="{{ route('customers.create') }}" class="btn btn-primary">{{ __('Add New Customer') }}</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('customers.index') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" 
                           placeholder="{{ __('Search by name, email, company...') }}" 
                           value="{{ request('search') }}">
                </div>
                
                @if(isset($tenants) && count($tenants) > 0)
                <div class="col-md-3">
                    <select name="tenant_id" class="form-select">
                        <option value="">{{ __('All Tenants') }}</option>
                        @foreach($tenants as $id => $name)
                            <option value="{{ $id }}" {{ request('tenant_id') == $id ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif
                
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">{{ __('All Statuses') }}</option>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" {{ request('status') == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i> {{ __('Filter') }}
                    </button>
                </div>
                
                @if(request()->hasAny(['search', 'status', 'tenant_id']))
                <div class="col-12 mt-2">
                    <a href="{{ route('customers.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-times me-1"></i> {{ __('Clear Filters') }}
                    </a>
                </div>
                @endif
            </form>
        </div>
    </div>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>{{ __('ID') }}</th>
                <th>{{ __('Full Name') }}</th>
                <th>{{ __('Email') }}</th>
                <th>{{ __('Phone') }}</th>
                <th>{{ __('Company') }}</th>
                @if(!$currentTenantId)
                <th>{{ __('Tenant') }}</th>
                @endif
                <th>{{ __('Status') }}</th>
                <th>{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($customers as $customer)
                <tr>
                    <td>{{ $customer->customer_id }}</td>
                    <td>{{ $customer->full_name }}</td>
                    <td>{{ $customer->email ?: __('N/A') }}</td>
                    <td>{{ $customer->phone_number ?: __('N/A') }}</td>
                    <td>{{ $customer->company_name ?? 'N/A' }}</td>
                    @if(!$currentTenantId)
                    <td>
                        <span class="badge bg-info">
                            {{ $customer->tenant->name ?? 'N/A' }}
                        </span>
                    </td>
                    @endif
                    <td>
                        <span class="badge bg-{{ $customer->status === 'Active' ? 'success' : 'secondary' }}">
                            {{ $customer->status }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('customers.show', $customer->customer_id) }}" class="btn btn-info btn-sm">{{ __('View') }}</a>
                        <a href="{{ route('customers.edit', $customer->customer_id) }}" class="btn btn-warning btn-sm">{{ __('Edit') }}</a>
                        <form action="{{ route('customers.destroy', $customer->customer_id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('{{ __('Are you sure you want to delete this customer?') }}');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">{{ __('Delete') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">{{ __('No customers found.') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="d-flex justify-content-center">
        {{ $customers->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection