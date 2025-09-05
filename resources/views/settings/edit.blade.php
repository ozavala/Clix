@extends('layouts.app')

@section('title', 'Application Settings')

@section('content')
<div class="container">
    <h1>Settings</h1>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Core Settings --}}
    <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PATCH')
        <div class="card mb-4">
            <div class="card-header">{{ __('messages.Core Settings') }}</div>
            <div class="card-body">
                <div class="row">
                    @foreach($coreSettings as $setting)
                        <div class="col-md-6 mb-3">
                            <label for="{{ $setting->key }}" class="form-label">{{ __(ucfirst(str_replace('_', ' ', $setting->key))) }}</label>
                            @if($setting->key === 'company_logo')
                                <input type="file" class="form-control" id="{{ $setting->key }}" name="{{ $setting->key }}">
                                @if ($setting->value)
                                    <img src="{{ asset('storage/' . $setting->value) }}" alt="Company Logo" class="img-thumbnail mt-2" style="max-height: 100px ; max-width: 100px">
                                @endif
                            @elseif($setting->key === 'default_locale')
                                <select class="form-control" id="{{ $setting->key }}" name="{{ $setting->key }}">
                                    <option value="en" @if(old($setting->key, $setting->value) === 'en') selected @endif>English</option>
                                    <option value="es" @if(old($setting->key, $setting->value) === 'es') selected @endif>Spanish</option>
                                </select>
                            @else
                                <input type="text" class="form-control" id="{{ $setting->key }}" name="{{ $setting->key }}" value="{{ old($setting->key, $setting->value) }}">
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Save</button>
        <a href="{{ route('settings.edit') }}" class="btn btn-secondary">Delete</a>
    </form>

    {{-- Custom Settings --}}
    <div class="card mt-5">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>{{ __('messages.Custom Settings') }}</span>
            <form action="{{ route('settings.custom.store') }}" method="POST" class="d-flex gap-2 align-items-center mb-0">
                @csrf
                <input type="text" name="key" class="form-control form-control-sm" placeholder="{{ __('messages.New Setting Key') }}" required>
                <input type="text" name="value" class="form-control form-control-sm" placeholder="{{ __('messages.New Setting Value') }}">
                <button type="submit" class="btn btn-success btn-sm">{{ __('messages.Add') }}</button>
            </form>
        </div>
        <div class="card-body p-0">
            <table class="table table-bordered mb-0">
                <thead>
                    <tr>
                        <th>Key</th>
                        <th>Value</th>
                        <th>Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customSettings as $setting)
                        <tr>
                            <td>{{ $setting->key }}</td>
                            <td>{{ $setting->value }}</td>
                            <td>
                                <form action="{{ route('settings.custom.destroy', $setting) }}" method="POST" onsubmit="return confirm('{{ __('messages.Are you sure?') }}');" style="display:inline-block;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Delete
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center">{{ __('messages.No results found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            <form action="{{ route('settings.custom.store') }}" method="POST" class="d-flex gap-2 align-items-center mb-0">
                @csrf
                <input type="text" name="key" class="form-control form-control-sm" placeholder="{{ __('messages.New Setting Key') }}" required>
                <input type="text" name="value" class="form-control form-control-sm" placeholder="{{ __('messages.New Setting Value') }}">
                <button type="submit" class="btn btn-success btn-sm">{{ __('messages.Add Personal Configuration') }}</button>
            </form>
        </div>
    </div>
    <a href="{{ route('settings.edit') }}" class="btn btn-secondary mt-3">Volver</a>
</div>
@endsection