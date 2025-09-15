@extends('layouts.app')

@section('title', 'Suppliers')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Proveedores</h1>
        <div class="d-flex align-items-center gap-2">
            @isset($tenants)
            <form action="{{ route('suppliers.index') }}" method="GET" class="d-flex align-items-center me-2">
                <label for="tenant_id" class="me-2 mb-0">Tenant:</label>
                <select name="tenant_id" id="tenant_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    @foreach($tenants as $t)
                        <option value="{{ $t->id }}" {{ (request('tenant_id') ?? ($requestedTenantId ?? '')) == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
            </form>
            @endisset
            <a href="{{ route('suppliers.create') }}" class="btn btn-primary">Nuevo Proveedor</a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="mb-3">
        <form action="{{ route('suppliers.index') }}" method="GET" class="d-flex">
            <input type="text" name="search" class="form-control me-2" placeholder="Buscar" value="{{ request('search') }}">
            <button type="submit" class="btn btn-outline-primary">Buscar</button>
            @if(request('search'))
                <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary ms-2">Limpiar</a>
            @endif
        </form>
    </div>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Contacto</th>
                <th>Correo</th>
                <th>Teléfono</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($suppliers as $supplier)
                <tr>
                    <td>{{ $supplier->supplier_id }}</td>
                    <td><a href="{{ route('suppliers.show', $supplier->supplier_id) }}">{{ $supplier->name }}</a></td>
                    <td>{{ $supplier->contact_person ?: 'N/A' }}</td>
                    <td>{{ $supplier->email ?: 'N/A' }}</td>
                    <td>{{ $supplier->phone_number ?: 'N/A' }}</td>
                    <td>
                        <a href="{{ route('suppliers.edit', $supplier->supplier_id) }}" class="btn btn-warning btn-sm">Editar</a>
                        <form action="{{ route('suppliers.destroy', $supplier->supplier_id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">No se encontraron resultados</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="d-flex justify-content-center">
        {{ $suppliers->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection