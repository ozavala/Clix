@extends('layouts.app')

@section('title', $purchaseOrder ? __('bills.Create Bill from PO') : __('bills.Create Bill'))

@section('content')
<div class="container">
    <h1>Create Bill</h1>

    <form action="{{ route('bills.store') }}" method="POST">
        @csrf
        <x-tenant-hidden :model="$bill ?? null" />
        @if($purchaseOrder)
            <input type="hidden" name="purchase_order_id" value="{{ $purchaseOrder->purchase_order_id }}">
        @endif

        <div class="card mb-4">
            <div class="card-header">Bill Details</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="supplier_id" class="form-label">Supplier</label>
                        @if($purchaseOrder)
                            <input type="hidden" name="supplier_id" value="{{ $purchaseOrder->supplier_id }}">
                            <input type="text" class="form-control" value="{{ $purchaseOrder->supplier->name }}" readonly>
                        @else
                            <select name="supplier_id" id="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror" required>
                                <option value="">{{ __('bills.Select a Supplier') }}</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->supplier_id }}" @selected(old('supplier_id') == $supplier->supplier_id)>{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                            @error('supplier_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="bill_number" class="form-label">Bill Number</label>
                        <input type="text" name="bill_number" id="bill_number" class="form-control @error('bill_number') is-invalid @enderror" value="{{ old('bill_number') }}" required>
                        @error('bill_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="bill_date" class="form-label">Bill Date</label>
                        <input type="date" name="bill_date" id="bill_date" class="form-control @error('bill_date') is-invalid @enderror" value="{{ old('bill_date', now()->toDateString()) }}" required>
                        @error('bill_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="due_date" class="form-label">Due Date</label>
                        <input type="date" name="due_date" id="due_date" class="form-control @error('due_date') is-invalid @enderror" value="{{ old('due_date', now()->addDays(30)->toDateString()) }}" required>
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
                        </tr>
                    </thead>
                    <tbody id="bill-items-body">
                        @if($purchaseOrder && $purchaseOrder->items->isNotEmpty())
                            @foreach($purchaseOrder->items as $index => $item)
                                <tr>
                                    <input type="hidden" name="items[{{ $index }}][purchase_order_item_id]" value="{{ $item->purchase_order_item_id }}">
                                    <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item->product_id }}">
                                    <td><input type="text" name="items[{{ $index }}][item_name]" class="form-control" value="{{ $item->item_name }}" readonly></td>
                                    <td><input type="text" name="items[{{ $index }}][item_description]" class="form-control" value="{{ $item->item_description }}" readonly></td>
                                    <td><input type="number" name="items[{{ $index }}][quantity]" class="form-control item-qty" value="{{ $item->quantity }}" required></td>
                                    <td><input type="number" name="items[{{ $index }}][unit_price]" class="form-control item-price" step="0.01" value="{{ $item->unit_price }}" required></td>
                                    <td><input type="text" class="form-control item-total" value="{{ number_format($item->item_total, 2) }}" readonly></td>
                                </tr>
                            @endforeach
                        @else
                            {{-- Add logic for dynamic rows if creating a standalone bill --}}
                            <tr>
                                <td><input type="text" name="items[0][item_name]" class="form-control" required></td>
                                <td><input type="text" name="items[0][item_description]" class="form-control"></td>
                                <td><input type="number" name="items[0][quantity]" class="form-control item-qty" required></td>
                                <td><input type="number" name="items[0][unit_price]" class="form-control item-price" step="0.01" required></td>
                                <td><input type="text" class="form-control item-total" readonly></td>
                            </tr>
                        @endif
                    </tbody> {{-- Add button to add more rows for standalone bills --}}
                </table>
                {{-- Add button to add more rows for standalone bills --}}
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Summary & Notes</div>
            <div class="card-body">
                 <div class="row">
                    <div class="col-md-6">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea name="notes" id="notes" rows="3" class="form-control">{{ old('notes') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        {{-- Add summary fields like subtotal, tax, total --}}
                        <div class="mb-2">
                            <p class="d-flex justify-content-between"><span>Subtotal:</span> <span id="subtotal-display">$0.00</span></p>
                        </div>
                        <div class="mb-2">
                            <label for="tax_amount" class="form-label">Tax Amount</label>
                            <input type="number" name="tax_amount" id="tax_amount" class="form-control" step="0.01" value="{{ old('tax_amount', 0) }}" min="0">
                        </div>
                        <div class="mb-2">
                            <p class="d-flex justify-content-between"><span>Total Amount:</span> <span id="total-amount-display">$0.00</span></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @include('bills._item_calculation_script')
        
        <div class="mt-3">
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route('bills.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection