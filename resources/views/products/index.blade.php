@extends('layouts.app')

@section('title', __('Products & Services'))

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>{{ __('Products & Services') }}</h1>
        <div class="d-flex align-items-center gap-2">
            @isset($tenants)
            <form action="{{ route('products.index') }}" method="GET" class="d-flex align-items-center me-2">
                <label for="tenant_id" class="me-2 mb-0">{{ __('Tenant') }}:</label>
                <select name="tenant_id" id="tenant_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">{{ __('All') }}</option>
                    @foreach($tenants as $t)
                        <option value="{{ $t->id }}" {{ (request('tenant_id') ?? ($requestedTenantId ?? '')) == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
            </form>
            @endisset
            <a href="{{ route('products.create') }}" class="btn btn-primary">{{ __('Add New') }}</a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="mb-3">
        <form action="{{ route('products.index') }}" method="GET">
            <div class="row g-2">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="{{ __('Search by name, SKU, description...') }}" value="{{ request('search') }}">
                </div>
                <div class="col-md-2 col-sm-6">
                    <select name="type_filter" class="form-select">
                        <option value="">{{ __('All Types') }}</option>
                        <option value="product" {{ request('type_filter') == 'product' ? 'selected' : '' }}>{{ __('Product') }}</option>
                        <option value="service" {{ request('type_filter') == 'service' ? 'selected' : '' }}>{{ __('Service') }}</option>
                    </select>
                </div>
                <div class="col-md-2 col-sm-6">
                    <select name="status_filter" class="form-select">
                        <option value="">{{ __('All Statuses') }}</option>
                        <option value="active" {{ request('status_filter') == 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                        <option value="inactive" {{ request('status_filter') == 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                    </select>
                </div>
                <div class="col-md-2 col-sm-6">
                    <select name="category_filter" class="form-select">
                        <option value="">{{ __('All Categories') }}</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->category_id }}" {{ request('category_filter') == $category->category_id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-sm-6 d-flex">
                    <button type="submit" class="btn btn-primary flex-grow-1">{{ __('Filter') }}</button>
                    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary ms-2" title="{{ __('Clear Filters') }}"><i class="bi bi-x-lg"></i> {{ __('Clear') }}</a>
                </div>
            </div>
        </form>
    </div>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>{{ __('ID') }}</th>
                <th>{{ __('Name') }}</th>
                <th>{{ __('SKU') }}</th>
                <th>{{ __('Category') }}</th>
                <th>{{ __('Type') }}</th>
                <th>{{ __('Price') }}</th>
                <th>{{ __('Stock') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($products as $product)
                <tr>
                    <td>{{ $product->product_id }}</td>
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->sku ?: __('N/A') }}</td>
                    <td>{{ $product->category->name ?? __('N/A') }}</td>
                    <td><span class="badge bg-{{ $product->is_service ? 'info' : 'secondary' }}">{{ $product->type_name }}</span></td>
                    <td>${{ number_format($product->price, 2) }}</td>
                    <td>{{ $product->is_service ? __('N/A') : $product->quantity_on_hand }}</td>
                    <td><span class="badge bg-{{ $product->is_active ? 'success' : 'danger' }}">{{ $product->is_active ? __('Active') : __('Inactive') }}</span></td>
                    <td>
                        <a href="{{ route('products.show', $product->product_id) }}" class="btn btn-info btn-sm">{{ __('View') }}</a>
                        <a href="{{ route('products.edit', $product->product_id) }}" class="btn btn-warning btn-sm">{{ __('Edit') }}</a>
                        <form action="{{ route('products.destroy', $product->product_id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('{{ __('Are you sure?') }}');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">{{ __('Delete') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">{{ __('No products or services found.') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="d-flex justify-content-center">
        {{ $products->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection