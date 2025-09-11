@extends('layouts.app')

@section('title', __('Select Tenant'))

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Select a Tenant') }}</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <div class="list-group">
                        @foreach($tenants as $tenant)
                            <a href="{{ route('tenants.switch', $tenant) }}" 
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                {{ $tenant->name }}
                                @if(session('current_tenant_id') == $tenant->id)
                                    <span class="badge bg-primary rounded-pill">{{ __('Current') }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
