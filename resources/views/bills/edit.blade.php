@extends('layouts.app')

@section('title', __('bills.Edit Bill') . ' #' . $bill->bill_number)

@section('content')
<div class="container">
    <h1>Edit Bill: #{{ $bill->bill_id }}</h1>

    <form action="{{ route('bills.update', $bill) }}" method="POST">
        @csrf
        @method('PUT')
        <x-tenant-hidden :model="$bill ?? null" />

        <div class="card mb-4">
            <div class="card-header">Bill Details</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="supplier_id" class="form-label">Supplier</label>
                        <select name="supplier_id" id="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror" required>
                            <option value="">{{ __('bills.Select a Supplier') }}</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->supplier_id }}" @selected(old('supplier_id', $bill->supplier_id) == $supplier->supplier_id)>{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                        @error('supplier_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="bill_number" class="form-label">Bill Number</label>
                        <input type="text" name="bill_number" id="bill_number" class="form-control @error('bill_number') is-invalid @enderror" value="{{ old('bill_number', $bill->bill_number) }}" required>
                        @error('bill_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                            @foreach($statuses as $key => $value)
                                <option value="{{ $key }}" @selected(old('status', $bill->status) == $key)>{{ $value }}</option>
                            @endforeach
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="bill_date" class="form-label">Bill Date</label>
                        <input type="date" name="bill_date" id="bill_date" class="form-control @error('bill_date') is-invalid @enderror" value="{{ old('bill_date', $bill->bill_date->format('Y-m-d')) }}" required>
                        @error('bill_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="due_date" class="form-label">Due Date</label>
                        <input type="date" name="due_date" id="due_date" class="form-control @error('due_date') is-invalid @enderror" value="{{ old('due_date', $bill->due_date->format('Y-m-d')) }}" required>
                        @error('due_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Bill Items</div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Description</th>
                            <th width="10%">Qty</th>
                            <th width="15%">Unit Price</th>
                            <th width="15%">Total</th>
                            <th width="5%"></th> {{-- For remove button --}}
                        </tr>
                    </thead>
                    <tbody id="bill-items-body">
                        @foreach(old('items', $bill->items) as $index => $item)
                            <tr data-item-id="{{ $item->bill_item_id ?? '' }}">
                                <input type="hidden" name="items[{{ $index }}][bill_item_id]" value="{{ $item->bill_item_id ?? '' }}">
                                <input type="hidden" name="items[{{ $index }}][purchase_order_item_id]" value="{{ $item->purchase_order_item_id ?? '' }}">
                                <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item->product_id ?? '' }}">
                                <td><input type="text" name="items[{{ $index }}][item_name]" class="form-control" value="{{ $item->item_name }}" required></td>
                                <td><input type="text" name="items[{{ $index }}][item_description]" class="form-control" value="{{ $item->item_description }}"></td>
                                <td><input type="number" name="items[{{ $index }}][quantity]" class="form-control item-qty" value="{{ $item->quantity }}" required></td>
                                <td><input type="number" name="items[{{ $index }}][unit_price]" class="form-control item-price" step="0.01" value="{{ $item->unit_price }}" required></td>
                                <td><input type="text" class="form-control item-total" value="{{ number_format($item->item_total, 2) }}" readonly></td>
                                <td><button type="button" class="btn btn-danger btn-sm remove-item">X</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <button type="button" class="btn btn-success btn-sm" id="add-item">Add Item</button>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Summary & Notes</div>
            <div class="card-body">
                 <div class="row">
                    <div class="col-md-6">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea name="notes" id="notes" rows="3" class="form-control">{{ old('notes', $bill->notes) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-2">
                            <p class="d-flex justify-content-between"><span>Subtotal:</span> <span id="subtotal-display">${{ number_format($bill->subtotal, 2) }}</span></p>
                        </div>
                        <div class="mb-2">
                            <label for="tax_amount" class="form-label">Tax Amount</label>
                            <input type="number" name="tax_amount" id="tax_amount" class="form-control" step="0.01" value="{{ old('tax_amount', $bill->tax_amount) }}" min="0">
                        </div>
                        <div class="mb-2">
                            <p class="d-flex justify-content-between"><span>Total Amount:</span> <span id="total-amount-display">${{ number_format($bill->total_amount, 2) }}</span></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-3">
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="{{ route('bills.show', $bill) }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

@include('bills._item_calculation_script')

@endsection