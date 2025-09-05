@extends('layouts.app')

@section('title', __('Customer') . ': ' . $customer->full_name)

@section('content')
<div class="container">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0">{{ $customer->full_name }}</h1>
            <p class="text-muted mb-0">{{ $customer->company_name }}</p>
        </div>
        <div>
            <span class="badge bg-primary fs-6 me-2">{{ $customer->status }}</span>
            <a href="{{ route('customers.edit', $customer->customer_id) }}" class="btn btn-warning">{{ __('Edit') }}</a>
            <a href="{{ route('customers.index') }}" class="btn btn-secondary">{{ __('Back to Customers') }}</a>
        </div>
    </div>

    {{-- Session Messages --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Tabs --}}
    <ul class="nav nav-tabs" id="customerDetailsTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab" aria-controls="details" aria-selected="true">{{ __('Details') }}</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button" role="tab" aria-controls="payments" aria-selected="false">{{ __('Payments') }}</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="contacts-tab" data-bs-toggle="tab" data-bs-target="#contacts" type="button" role="tab" aria-controls="contacts" aria-selected="false">{{ __('Contacts') }}</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="notes-tab" data-bs-toggle="tab" data-bs-target="#notes" type="button" role="tab" aria-controls="notes" aria-selected="false">{{ __('Notes') }}</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="invoices-tab" data-bs-toggle="tab" data-bs-target="#invoices" type="button" role="tab" aria-controls="invoices" aria-selected="false">{{ __('Invoices') }}</button>
        </li>
        {{-- Other tabs can be added here: Contacts, Opportunities, Orders, etc. --}}
    </ul>

    <div class="tab-content" id="customerDetailsTabContent">
        {{-- Details Tab --}}
        <div class="tab-pane fade show active" id="details" role="tabpanel" aria-labelledby="details-tab">
            <div class="card card-body border-top-0">
                <div class="row">
                    <div class="col-md-6">
                        <h5>{{ __('Primary Information') }}</h5>
                        @if($customer->type === 'Company')
                            <p><strong>{{ __('Company Name') }}:</strong> {{ $customer->company_name }}</p>
                        @else
                            <p><strong>{{ __('Name') }}:</strong> {{ $customer->first_name }} {{ $customer->last_name }}</p>
                        @endif
                        <p><strong>{{ __('Legal ID') }}:</strong> {{ $customer->legal_id }}</p>
                        <p><strong>{{ __('Email') }}:</strong> {{ $customer->email ?? __('N/A') }}</p>
                        <p><strong>{{ __('Phone') }}:</strong> {{ $customer->phone_number }}</p>
                    </div>
                    <div class="col-md-6">
                        <h5>{{ __('Addresses') }}</h5>
                        @forelse($customer->addresses as $address)
                            <p>
                                <strong>{{ $address->address_name ?? __('Primary Address') }}:</strong><br>
                                {{ $address->street_address_line_1 }}<br>
                                @if($address->street_address_line_2){{ $address->street_address_line_2 }}<br>@endif
                                {{ $address->city }}, {{ $address->state_province }} {{ $address->postal_code }}
                            </p>
                        @empty
                            <p>{{ __('No addresses on file.') }}</p>
                        @endforelse
                    </div>
                </div>
                
                {{-- Payment Summary --}}
                @if($payments->isNotEmpty())
                <div class="row mt-4">
                    <div class="col-12">
                        <h5>{{ __('Recent Payments') }}</h5>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>{{ __('Date') }}</th>
                                        <th>{{ __('Amount') }}</th>
                                        <th>{{ __('For') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($payments->take(3) as $payment)
                                        <tr>
                                            <td>{{ $payment->payment_date->format('Y-m-d') }}</td>
                                            <td>${{ number_format($payment->amount, 2) }}</td>
                                            <td>
                                                @if ($payment->payable)
                                                    @if ($payment->payable instanceof \App\Models\Invoice)
                                                        {{ __('Invoice') }} #{{ $payment->payable->invoice_number }}
                                                    @elseif ($payment->payable instanceof \App\Models\Order)
                                                        {{ __('Order') }} #{{ $payment->payable->order_number }}
                                                    @else
                                                        {{ class_basename($payment->payable) }} #{{ $payment->payable->getKey() }}
                                                    @endif
                                                @else
                                                    {{ __('N/A') }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Payments Tab --}}
        <div class="tab-pane fade" id="payments" role="tabpanel" aria-labelledby="payments-tab">
            <div class="card card-body border-top-0">
                <h5>{{ __('Payment History') }}</h5>
                @if($payments->isEmpty())
                    <p>{{ __('No payments have been recorded for this customer.') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Amount') }}</th>
                                    <th>{{ __('Method') }}</th>
                                    <th>{{ __('Paid For') }}</th>
                                    <th>{{ __('Recorded By') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($payments as $payment)
                                    <tr>
                                        <td>{{ $payment->payment_date->format('Y-m-d') }}</td>
                                        <td>${{ number_format($payment->amount, 2) }}</td>
                                        <td>{{ $payment->payment_method }}</td>
                                        <td>
                                            @if ($payment->payable)
                                                @if ($payment->payable instanceof \App\Models\Order)
                                                    <a href="{{ route('orders.show', $payment->payable->order_id) }}">
                                                        {{ __('Order') }} #{{ $payment->payable->order_number }}
                                                    </a>
                                                @elseif ($payment->payable instanceof \App\Models\Invoice)
                                                    <a href="{{ route('invoices.show', $payment->payable->invoice_id) }}">
                                                        {{ __('Invoice') }} #{{ $payment->payable->invoice_number }}
                                                    </a>
                                                @else
                                                    {{ class_basename($payment->payable) }} #{{ $payment->payable->getKey() }}
                                                @endif
                                            @else
                                                {{ __('N/A') }}
                                            @endif
                                        </td>
                                        <td>{{ $payment->createdBy->full_name ?? __('N/A') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- Notes Tab --}}
        <div class="tab-pane fade" id="notes" role="tabpanel" aria-labelledby="notes-tab">
            <div class="card card-body border-top-0">
                <h5>{{ __('Notes') }}</h5>
                @if($customer->notes->isEmpty())
                    <p>{{ __('No notes have been added for this customer.') }}</p>
                @else
                    <div class="notes-list">
                        @foreach($customer->notes as $note)
                            <div class="note-item border-bottom pb-3 mb-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <p class="mb-1">{{ $note->body }}</p>
                                        <small class="text-muted">
                                            {{ __('Added by') }} {{ $note->createdBy->full_name ?? __('Unknown') }} {{ __('on') }} {{ $note->created_at->format('M d, Y \a\t g:i A') }}
                                        </small>
                                    </div>
                                    <form action="{{ route('notes.destroy', $note) }}" method="POST" class="ms-2">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('{{ __('Are you sure you want to delete this note?') }}')">
                                            {{ __('Delete') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
                
                {{-- Add Note Form --}}
                <div class="mt-4">
                    <h6>{{ __('Add New Note') }}</h6>
                    <form action="{{ route('notes.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="noteable_id" value="{{ $customer->customer_id }}">
                        <input type="hidden" name="noteable_type" value="Customer">
                        <div class="mb-3">
                            <textarea name="body" class="form-control" rows="3" placeholder="{{ __('Enter your note here...') }}" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">{{ __('Add Note') }}</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Invoices Tab --}}
        <div class="tab-pane fade" id="invoices" role="tabpanel" aria-labelledby="invoices-tab">
            <div class="card card-body border-top-0">
                <h5>{{ __('Invoices') }}</h5>
                @if($invoices->isEmpty())
                    <p>{{ __('No invoices found for this customer.') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{{ __('Invoice') }} #</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Total') }}</th>
                                    <th>{{ __('Amount Paid') }}</th>
                                    <th>{{ __('Amount Due') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoices as $invoice)
                                    <tr>
                                        <td>{{ $invoice->invoice_number }}</td>
                                        <td>{{ $invoice->invoice_date->format('Y-m-d') }}</td>
                                        <td>{{ $invoice->status }}</td>
                                        <td>${{ number_format($invoice->total_amount, 2) }}</td>
                                        <td>${{ number_format($invoice->amount_paid, 2) }}</td>
                                        <td>${{ number_format($invoice->amount_due, 2) }}</td>
                                        <td><a href="{{ route('invoices.show', $invoice->invoice_id) }}" class="btn btn-sm btn-info">{{ __('View') }}</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>