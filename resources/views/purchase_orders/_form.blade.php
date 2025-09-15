@csrf
<x-tenant-hidden :model="$purchaseOrder ?? null" />
<div class="row">
    <div class="col-md-4 mb-3">
        <label for="purchase_order_number" class="form-label">PO Number</label>
        <input type="text" class="form-control @error('purchase_order_number') is-invalid @enderror" id="purchase_order_number" name="purchase_order_number" value="{{ old('purchase_order_number', $purchaseOrder->purchase_order_number ?? '') }}">
        @error('purchase_order_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4 mb-3">
        <label for="supplier_id" class="form-label">Supplier <span class="text-danger">*</span></label>
        <select class="form-select @error('supplier_id') is-invalid @enderror" id="supplier_id" name="supplier_id" required>
            <option value="">Select Supplier</option>
            @foreach($suppliers as $supplier)
                <option value="{{ $supplier->supplier_id }}" {{ (old('supplier_id', $purchaseOrder->supplier_id ?? '') == $supplier->supplier_id) ? 'selected' : '' }}>
                    {{ $supplier->name }}
                </option>
            @endforeach
        </select>
        @error('supplier_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4 mb-3">
        <label for="shipping_address_id" class="form-label">Ship To (Your Address)</label>
        <select class="form-select @error('shipping_address_id') is-invalid @enderror" id="shipping_address_id" name="shipping_address_id">
            <option value="">Select Shipping Address</option>
            @foreach($companyAddresses as $address)
                <option value="{{ $address->address_id }}" {{ (old('shipping_address_id', $purchaseOrder->shipping_address_id ?? '') == $address->address_id) ? 'selected' : '' }}>
                    {{ $address->street_address_line_1 }}, {{ $address->city }} ({{ $address->address_type ?? class_basename($address->addressable_type) }})
                </option>
            @endforeach
        </select>
        @error('shipping_address_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-3 mb-3">
        <label for="order_date" class="form-label">Order Date <span class="text-danger">*</span></label>
        <input type="date" class="form-control @error('order_date') is-invalid @enderror" id="order_date" name="order_date" value="{{ old('order_date', ($purchaseOrder->order_date ?? now())->format('Y-m-d')) }}" required>
        @error('order_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3 mb-3">
        <label for="expected_delivery_date" class="form-label">Expected Delivery Date</label>
        <input type="date" class="form-control @error('expected_delivery_date') is-invalid @enderror" id="expected_delivery_date" name="expected_delivery_date" value="{{ old('expected_delivery_date', ($purchaseOrder->expected_delivery_date ?? now()->addDays(14))->format('Y-m-d')) }}">
        @error('expected_delivery_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3 mb-3">
        <label for="type" class="form-label">Type</label>
        <select class="form-select @error('type') is-invalid @enderror" id="type" name="type">
            @foreach($types as $key => $value)
                <option value="{{ $key }}" {{ old('type', $purchaseOrder->type ?? 'Standard') == $key ? 'selected' : '' }}>{{ $value }}</option>
            @endforeach
        </select>
        @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3 mb-3">
        <label for="status" class="form-label">Status</label>
        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
            @foreach($statuses as $key => $value)
                <option value="{{ $key }}" {{ old('status', $purchaseOrder->status ?? 'Draft') == $key ? 'selected' : '' }}>{{ $value }}</option>
            @endforeach
        </select>
        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<hr>
<h4>Line Items</h4>
<div id="po-items-container">
    @php
        $currentItems = old('items', (isset($purchaseOrder) ? $purchaseOrder->items->toArray() : [['product_id' => '', 'item_name' => '', 'item_description' => '', 'quantity' => 1, 'unit_price' => '']]));
        $itemIndex = 0;
    @endphp

    @foreach($currentItems as $index => $item)
        @php $itemIndex = $index; @endphp
        <div class="row align-items-center mb-2 po-item-row border p-2 rounded">
            <input type="hidden" name="items[{{ $index }}][purchase_order_item_id]" value="{{ $item['purchase_order_item_id'] ?? '' }}">
            <div class="col-md-3 mb-2">
                <label class="form-label">Product</label>
                <select name="items[{{ $index }}][product_id]" class="form-select product-select">
                    <option value="">Select Product</option>
                    @foreach($products as $product)
                        <option value="{{ $product->product_id }}" data-price="{{ $product->cost ?? $product->price }}" data-description="{{ $product->description }}" {{ ($item['product_id'] ?? '') == $product->product_id ? 'selected' : '' }}>
                            {{ $product->name }} (SKU: {{ $product->sku ?? 'N/A' }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 mb-2">
                <label class="form-label">Item Name <span class="text-danger">*</span></label>
                <input type="text" name="items[{{ $index }}][item_name]" class="form-control item-name" value="{{ $item['item_name'] ?? '' }}" required>
            </div>
            <div class="col-md-2 mb-2">
                <label class="form-label">Quantity <span class="text-danger">*</span></label>
                <input type="number" name="items[{{ $index }}][quantity]" class="form-control item-quantity" value="{{ $item['quantity'] ?? 1 }}" min="1" required>
            </div>
            <div class="col-md-2 mb-2">
                <label class="form-label">Unit Price <span class="text-danger">*</span></label>
                <input type="number" step="0.01" name="items[{{ $index }}][unit_price]" class="form-control item-unit-price" value="{{ $item['unit_price'] ?? '' }}" min="0" required>
            </div>
            <div class="col-md-1 mb-2 d-flex align-items-end">
                <button type="button" class="btn btn-danger remove-item-btn">&times;</button>
            </div>
            <div class="col-md-12 mb-2">
                <label class="form-label">Description</label>
                <textarea name="items[{{ $index }}][item_description]" class="form-control item-description" rows="1">{{ $item['item_description'] ?? '' }}</textarea>
            </div>
        </div>
    @endforeach
</div>
<button type="button" id="add-item-btn" class="btn btn-success btn-sm mt-2">Add Item</button>
@error('items') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
@foreach ($errors->get('items.*') as $message)
    <div class="text-danger small mt-1">{{ $message[0] }}</div>
@endforeach

<hr>

<div class="row justify-content-end">
    <div class="col-md-5">
        {{-- Discount fields can be added here if needed for POs --}}
        <div class="row mb-2">
            <label for="tax_percentage" class="col-sm-4 col-form-label">Tax (%)</label>
            <div class="col-sm-8">
                <input type="number" step="0.01" name="tax_percentage" id="tax_percentage" class="form-control @error('tax_percentage') is-invalid @enderror" value="{{ old('tax_percentage', $purchaseOrder->tax_percentage ?? 0) }}">
                @error('tax_percentage') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="row mb-2">
            <label for="shipping_cost" class="col-sm-4 col-form-label">Shipping Cost</label>
            <div class="col-sm-8">
                <input type="number" step="0.01" name="shipping_cost" id="shipping_cost" class="form-control @error('shipping_cost') is-invalid @enderror" value="{{ old('shipping_cost', $purchaseOrder->shipping_cost ?? 0) }}">
                @error('shipping_cost') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
         <div class="row mb-2">
            <label for="other_charges" class="col-sm-4 col-form-label">Other Charges</label>
            <div class="col-sm-8">
                <input type="number" step="0.01" name="other_charges" id="other_charges" class="form-control @error('other_charges') is-invalid @enderror" value="{{ old('other_charges', $purchaseOrder->other_charges ?? 0) }}">
                @error('other_charges') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>
</div>

<div class="mb-3">
    <label for="terms_and_conditions" class="form-label">Terms & Conditions</label>
    <textarea class="form-control @error('terms_and_conditions') is-invalid @enderror" id="terms_and_conditions" name="terms_and_conditions" rows="3">{{ old('terms_and_conditions', $purchaseOrder->terms_and_conditions ?? '') }}</textarea>
    @error('terms_and_conditions') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="notes" class="form-label">Notes</label>
    <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes', $purchaseOrder->notes ?? '') }}</textarea>
    @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mt-4">
    <button type="submit" class="btn btn-primary">Save</button>
    <a href="{{ route('purchase-orders.index') }}" class="btn btn-secondary">Cancel</a>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const itemsContainer = document.getElementById('po-items-container');
    const addItemBtn = document.getElementById('add-item-btn');
    let itemRowIndex = {{ $itemIndex + 1 }};

    addItemBtn.addEventListener('click', function () {
        const newRow = document.createElement('div');
        newRow.classList.add('row', 'align-items-center', 'mb-2', 'po-item-row', 'border', 'p-2', 'rounded');
        newRow.innerHTML = `
            <input type="hidden" name="items[${itemRowIndex}][purchase_order_item_id]" value="">
            <div class="col-md-3 mb-2"><label class="form-label">Product</label><select name="items[${itemRowIndex}][product_id]" class="form-select product-select"><option value="">Select Product</option>@foreach($products as $product)<option value="{{ $product->product_id }}" data-price="{{ $product->cost ?? $product->price }}" data-description="{{ $product->description }}">{{ $product->name }} (SKU: {{ $product->sku ?? 'N/A' }})</option>@endforeach</select></div>
            <div class="col-md-3 mb-2"><label class="form-label">Item Name <span class="text-danger">*</span></label><input type="text" name="items[${itemRowIndex}][item_name]" class="form-control item-name" required></div>
            <div class="col-md-2 mb-2"><label class="form-label">Quantity <span class="text-danger">*</span></label><input type="number" name="items[${itemRowIndex}][quantity]" class="form-control item-quantity" value="1" min="1" required></div>
            <div class="col-md-2 mb-2"><label class="form-label">Unit Price <span class="text-danger">*</span></label><input type="number" step="0.01" name="items[${itemRowIndex}][unit_price]" class="form-control item-unit-price" min="0" required></div>
            <div class="col-md-1 mb-2 d-flex align-items-end"><button type="button" class="btn btn-danger remove-item-btn">&times;</button></div>
            <div class="col-md-12 mb-2"><label class="form-label">Description</label><textarea name="items[${itemRowIndex}][item_description]" class="form-control item-description" rows="1"></textarea></div>
        `;
        itemsContainer.appendChild(newRow);
        itemRowIndex++;
    });

    itemsContainer.addEventListener('click', function (event) {
        if (event.target.classList.contains('remove-item-btn')) {
            event.target.closest('.po-item-row').remove();
        }
    });

    itemsContainer.addEventListener('change', function(event) {
        if (event.target.classList.contains('product-select')) {
            const selectedOption = event.target.options[event.target.selectedIndex];
            const row = event.target.closest('.po-item-row');
            if (selectedOption.value) {
                const productNameFull = selectedOption.text.trim();
                const skuMatch = productNameFull.match(/\(SKU:.*\)/);
                const productName = skuMatch ? productNameFull.replace(skuMatch[0], '').trim() : productNameFull;

                row.querySelector('.item-name').value = productName;
                row.querySelector('.item-unit-price').value = selectedOption.dataset.price || ''; // Use cost for POs
                row.querySelector('.item-description').value = selectedOption.dataset.description || '';
            }
        }
    });
});
</script>
@endpush