@extends('layouts.app')

@section('title', __('Select Tenant'))

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>{{ __('Select a Tenant') }}</span>
                    @if($currentTenant)
                        <span class="badge bg-success">
                            {{ __('Current Primary Tenant: :name', ['name' => $currentTenant->name]) }}
                        </span>
                    @endif
                </div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-{{ session('status-type', 'success') }} alert-dismissible fade show" role="alert">
                            {{ session('status') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="list-group">
                        @forelse($tenants as $tenant)
                            <form method="POST" action="{{ route('tenants.switch', $tenant) }}" class="w-100">
                                @csrf
                                <button type="submit" 
                                    class="list-group-item list-group-item-action d-flex justify-content-between align-items-center w-100 text-start">
                                    <div>
                                        {{ $tenant->name }}
                                        @if($currentTenant && $tenant->id === $currentTenant->id)
                                            <span class="badge bg-primary ms-2">{{ __('Primary') }}</span>
                                        @endif
                                    </div>
                                    @if(session('current_tenant_id') == $tenant->id)
                                        <span class="badge bg-success rounded-pill">
                                            <i class="fas fa-check-circle me-1"></i> {{ __('Active') }}
                                        </span>
                                    @endif
                                </button>
                            </form>
                        @empty
                            <div class="alert alert-info">
                                {{ __('No tenants available. Please contact an administrator.') }}
                            </div>
                        @endforelse
                    </div>

                    @if(auth()->user()->isSuperAdmin())
                        <div class="mt-4">
                            <a href="{{ route('tenants.create') }}" class="btn btn-outline-primary">
                                <i class="fas fa-plus me-1"></i> {{ __('Add New Tenant') }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
