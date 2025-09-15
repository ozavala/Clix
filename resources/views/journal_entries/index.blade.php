@extends('layouts.app')

@section('title', __('journal_entries.Journal Entries'))

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Journal Entries</h1>
        <div class="d-flex align-items-center gap-2">
            @isset($tenants)
            <form action="{{ route('journal-entries.index') }}" method="GET" class="d-flex align-items-center me-2">
                <label for="tenant_id" class="me-2 mb-0">Tenant:</label>
                <select name="tenant_id" id="tenant_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All</option>
                    @foreach($tenants as $t)
                        <option value="{{ $t->id }}" {{ (request('tenant_id') ?? ($requestedTenantId ?? '')) == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
            </form>
            @endisset
            <a href="{{ route('journal-entries.create') }}" class="btn btn-primary">Add Journal Entry</a>
        </div>
    </div>

    {{-- Add Filter Form Later if needed --}}
    {{-- <div class="mb-3 card card-body">
        <form action="{{ route('journal-entries.index') }}" method="GET" class="row g-3">
            <div class="col-md-5">
                <input type="text" name="search_description" class="form-control form-control-sm" placeholder="Search description..." value="{{ request('search_description') }}">
            </div>
            <div class="col-md-3">
                <select name="transaction_type" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    @foreach($transactionTypes as $type)
                        <option value="{{ $type }}" {{ request('transaction_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('journal-entries.index') }}" class="btn btn-outline-secondary btn-sm w-100">Clear</a>
            </div>
        </form>
    </div> --}}

    <table class="table table-sm table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Type</th>
                <th>Description</th>
                <th>Reference</th>
                <th>Created By</th>
                <th class="text-end">Debits</th>
                <th class="text-end">Credits</th>
                <th>Lines</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($journalEntries as $entry)
                <tr>
                    <td><a href="{{ route('journal-entries.show', $entry->journal_entry_id) }}">{{ $entry->journal_entry_id }}</a></td>
                    <td>{{ $entry->entry_date->format('Y-m-d') }}</td>
                    <td>{{ $entry->transaction_type }}</td>
                    <td>{{ Str::limit($entry->description, 70) }}</td>
                    <td>
                        @if($entry->referenceable)
                            {{ class_basename($entry->referenceable_type) }} #{{ $entry->referenceable_id }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td>{{ $entry->createdBy->username ?? 'N/A' }}</td>
                    <td class="text-end">${{ number_format($entry->lines->sum('debit_amount'), 2) }}</td>
                    <td class="text-end">${{ number_format($entry->lines->sum('credit_amount'), 2) }}</td>
                    <td>
                        @foreach($entry->lines as $line)
                            <div><strong>{{ $line->account_name }}</strong><br><small>{{ $line->description ?? '-' }}</small></div>
                        @endforeach
                    </td>
                    <td>
                        @if(!$entry->referenceable_id) {{-- Only allow edit/delete for manual entries --}}
                            <a href="{{ route('journal-entries.edit', $entry->journal_entry_id) }}" class="btn btn-warning btn-sm">Edit</a>
                            <form action="{{ route('journal-entries.destroy', $entry->journal_entry_id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center">No journal entries found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="d-flex justify-content-center">
        {{ $journalEntries->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection