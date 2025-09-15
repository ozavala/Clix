@extends('layouts.app')

@section('title', 'Email Templates')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Email Templates</h3>
                    <div class="d-flex align-items-center gap-2">
                        @isset($tenants)
                        <form action="{{ route('email-templates.index') }}" method="GET" class="d-flex align-items-center me-2">
                            <label for="tenant_id" class="me-2 mb-0">Tenant:</label>
                            <select name="tenant_id" id="tenant_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">All</option>
                                @foreach($tenants as $t)
                                    <option value="{{ $t->id }}" {{ (request('tenant_id') ?? ($requestedTenantId ?? '')) == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                                @endforeach
                            </select>
                        </form>
                        @endisset
                        <a href="{{ route('email-templates.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create Template
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Subject</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($templates as $template)
                                    <tr>
                                        <td>
                                            <a href="{{ route('email-templates.show', $template) }}" class="text-decoration-none">
                                                {{ $template->name }}
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ ucfirst($template->type) }}</span>
                                        </td>
                                        <td>{{ $template->subject }}</td>
                                        <td>
                                            @if($template->is_active)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-secondary">Inactive</span>
                                            @endif
                                        </td>
                                        <td>{{ $template->created_at->format('M d, Y') }}</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('email-templates.show', $template) }}" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('email-templates.edit', $template) }}" 
                                                   class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="{{ route('email-templates.preview', $template) }}" 
                                                   class="btn btn-sm btn-outline-info" target="_blank">
                                                    <i class="fas fa-eye"></i> Preview
                                                </a>
                                                <form action="{{ route('email-templates.toggle-active', $template) }}" 
                                                      method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-warning">
                                                        <i class="fas fa-toggle-on"></i>
                                                    </button>
                                                </form>
                                                <form action="{{ route('email-templates.duplicate', $template) }}" 
                                                      method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-info">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </form>
                                                <form action="{{ route('email-templates.destroy', $template) }}" 
                                                      method="POST" class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to delete this template?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">No templates found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center">
                        {{ $templates->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 