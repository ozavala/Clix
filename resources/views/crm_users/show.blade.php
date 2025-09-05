@extends('layouts.app')

@section('title', 'CRM User Details')

@section('content')
<div class="container">
    <h1>{{ __('messages.CRM User') }}: {{ $crmUser->name }}</h1>

    <div class="card">
        <div class="card-header">
            ID: {{ $crmUser->user_id }}
        </div>
        <div class="card-body">
            <h5 class="card-title">{{ $crmUser->name }}</h5>
            <p><strong>{{ __('messages.Name') }}:</strong> {{ $crmUser->name }}</p>
            <p><strong>{{ __('messages.Email') }}:</strong> {{ $crmUser->email }}</p>
            <p><strong>{{ __('messages.Roles') }}:</strong>
                @forelse($crmUser->roles as $role)
                    <span class="badge bg-info">{{ $role->name }}</span>
                @empty
                    {{ __('messages.No roles assigned') }}
                @endforelse
            </p>
            <p><strong>{{ __('messages.Status') }}:</strong> {{ $crmUser->status }}</p>
            <p class="card-text"><strong>{{ __('messages.Created At') }}:</strong> {{ $crmUser->created_at->format('Y-m-d H:i:s') }}</p>
            <p class="card-text"><strong>{{ __('messages.Updated At') }}:</strong> {{ $crmUser->updated_at->format('Y-m-d H:i:s') }}</p>
        </div>
        <div class="card-footer">
            <a href="{{ route('crm-users.edit', $crmUser->user_id) }}" class="btn btn-warning">{{ __('messages.Edit') }}</a>
            <form action="{{ route('crm-users.destroy', $crmUser->user_id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este usuario?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">{{ __('messages.Delete') }}</button>
            </form>
            <a href="{{ route('crm-users.index') }}" class="btn btn-secondary">{{ __('messages.Back to List') }}</a>
        </div>
    </div>
</div>
@endsection