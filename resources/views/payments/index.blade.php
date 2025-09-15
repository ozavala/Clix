@extends('layouts.app')

@section('title', 'Payments') 

@section('content')
 <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Payments</h1>
        <div class="d-flex align-items-center gap-2">
            @isset($tenants)
            <form action="{{ route('payments.index') }}" method="GET" class="d-flex align-items-center me-2">
                <label for="tenant_id" class="me-2 mb-0">Tenant:</label>
                <select name="tenant_id" id="tenant_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All</option>
                    @foreach($tenants as $t)
                        <option value="{{ $t->id }}" {{ (request('tenant_id') ?? ($requestedTenantId ?? '')) == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
            </form>
            @endisset
        </div>
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
            <button type="button" class="btb-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="mb-3">
        <form action="{{ route('payments.index') }}" method="GET" class="d-flex">
            <input type="text" name="search" class="form-control me-2" placeholder="Search payments..." value="{{ request('search') }}">
            <button type="submit" class="btn btn-outline-primary">Search</button>
            @if(request('search'))
                <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary ms-2">Clear</a>
            @endif
        </form>
    </div>

    <table class="table table-striped">
        <thead> 
            <tr>
                <th>ID</th>
                <th>Payment Date</th>
                <th>Amount</th>
                <th>Payment Method</th>
                <th>Reference </th>
                <th>Notes</th>
                <th>Created By</th>
                <th>Actions </th>
            </tr>
        </thead>
        <tbody>
            @forelse ($payments as $payment)
                <tr>
                    <td>{{ $payment->payment_id }}</td>
                    <td>{{ $payment->payment_date }}</td>   
                    <td>{{ $payment->amount }}</td>
                    <td>{{ $payment->payment_method }}</td>
                    <td>{{ $payment->reference_number }}</td>
                    <td>{{ $payment->notes }}</td>
                    <td>{{ $payment->created_by_user_id }}</td>   
                    <td>
                        <a href="{{ route('payments.show', $payment->payment_id) }}" class="btn btn-info btn-sm">View</a>

                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center">No payments found.</td>
                </tr>
                @endforelse
        </tbody>
    </table>
    <div class="d-flex justify-content-center">
        {{ $payments->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection

                        





    
    