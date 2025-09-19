@extends('layouts.app')

@section('title', 'CRM Users')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Usuarios CRM</h1>
        <a href="{{ route('crm-users.create') }}" class="btn btn-primary">{{ __('messages.Add New') }}</a>
    </div>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>{{ __('messages.ID') }}</th>
                <th>{{ __('messages.Name') }}</th>
                <th>{{ __('messages.Email') }}</th>
                <th>{{ __('messages.Roles') }}</th>
                <th>{{ __('messages.Status') }}</th>
                <th>{{ __('messages.Actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($crmUsers as $user)
                <tr>
                    <td>{{ $user->user_id }}</td>
                    <td>{{ $user->username }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        @forelse($user->roles as $role)
                            <span class="badge bg-secondary">{{ $role->name }}</span>
                        @empty
                            No roles
                        @endforelse
                    </td>
                    <td>
                        <a href="{{ route('crm-users.show', $user->user_id) }}" class="btn btn-info btn-sm">Ver</a>
                        <a href="{{ route('crm-users.edit', $user->user_id) }}" class="btn btn-warning btn-sm">{{ __('messages.Edit') }}</a>
                        <form action="{{ route('crm-users.destroy', $user->user_id) }}" method="POST" style="display:inline-block;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro?')">{{ __('messages.Delete') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">{{ __('messages.No results found') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="d-flex justify-content-center">
        {{ $crmUsers->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection