@extends('layouts.app')

@section('title', 'Create CRM User')

@section('content')
<div class="container">
    <h1>{{ __('messages.Create') }} {{ __('messages.CRM User') }}</h1>

    <form action="{{ route('crm-users.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control @error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username') }}" required>
            @error('username')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="full_name" class="form-label">Full Name</label>
            <input type="text" class="form-control @error('full_name') is-invalid @enderror" id="full_name" name="full_name" value="{{ old('full_name') }}" required>
            @error('full_name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="password_confirmation" class="form-label">Confirm Password</label>
            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Roles</label>
            @foreach($roles as $role)
                <div class="form-check">
                    <input class="form-check-input @error('roles.'.$role->role_id) is-invalid @enderror"
                           type="checkbox"
                           name="roles[]"
                           value="{{ $role->role_id }}"
                           id="role_{{ $role->role_id }}"
                           {{ (is_array(old('roles')) && in_array($role->role_id, old('roles'))) ? 'checked' : '' }}>
                    <label class="form-check-label" for="role_{{ $role->role_id }}">{{ $role->name }}</label>
                </div>
            @endforeach
            @error('roles.*') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>

        <button type="submit" class="btn btn-primary">{{ __('messages.Save') }}</button>
        <a href="{{ route('crm-users.index') }}" class="btn btn-secondary">{{ __('messages.Cancel') }}</a>
    </form>
</div>
@endsection