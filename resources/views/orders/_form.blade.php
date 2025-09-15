@csrf
<x-tenant-hidden :model="$order ?? null" />
<div class="row">
    <div class="col-md-4 mb-3">
        <label for="customer_id" class="form-label">Customer <span class="text-danger">*</span></label>
        <select class="form-select @error('customer_id') is-invalid @enderror" id="customer_id" name="customer_id" required>
            <option value="">Select Customer</option>
            @foreach($customers as $customer)
                <option value="{{ $customer->customer_id }}" data-addresses="{{ json_encode($customer->addresses) }}"
                    {{ (old('customer_id', $order->customer_id ?? $selectedCustomerId ?? '') == $customer->customer_id) ? 'selected' : '' }}>
                    {{ $customer->full_name }} ({{ $customer->company_name ?? 'N/A' }})
                </option>
            @endforeach
        </select>
        @error('customer_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4 mb-3">
        <label for="order_number" class="form-label">Order Number (Optional)</label>
        <input type="text" class="form-control @error('order_number') is-invalid @enderror" id="order_number" name="order_number" value="{{ old('order_number', $order->order_number ?? '') }}">
        @error('order_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4 mb-3">
        <label for="order_date" class="form-label">Order Date <span class="text-danger">*</span></label>
        <input type="date" class="form-control @error('order_date') is-invalid @enderror" id="order_date" name="order_date" value="{{ old('order_date', ($order->order_date ?? now())->format('Y-m-d')) }}" required>
        @error('order_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label for="quotation_id" class="form-label">Related Quotation (Optional)</label>
        <select class="form-select @error('quotation_id') is-invalid @enderror" id="quotation_id" name="quotation_id">
            <option value="">None</option>
            @foreach($quotations as $quotation)
                <option value="{{ $quotation->quotation_id }}" {{ (old('quotation_id', $order->quotation_id ?? $selectedQuotationId ?? '') == $quotation->quotation_id) ? 'selected' : '' }}>
                    {{ $quotation->subject }}
                </option>
            @endforeach
        </select>
        @error('quotation_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4 mb-3">
        <label for="opportunity_id" class="form-label">Related Opportunity (Optional)</label>
        <select class="form-select @error('opportunity_id') is-invalid @enderror" id="opportunity_id" name="opportunity_id">
            <option value="">None</option>
            @foreach($opportunities as $opportunity)
                <option value="{{ $opportunity->opportunity_id }}" {{ (old('opportunity_id', $order->opportunity_id ?? $selectedOpportunityId ?? '') == $opportunity->opportunity_id) ? 'selected' : '' }}>
                    {{ $opportunity->name }}
                </option>
            @endforeach
        </select>
        @error('opportunity_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4 mb-3">
        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
            @foreach($statuses as $key => $value)
                <option value="{{ $key }}" {{ old('status', $order->status ?? 'Pending') == $key ? 'selected' : '' }}>{{ $value }}</option>
            @endforeach
        </select>
        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="shipping_address_id" class="form-label">Shipping Address</label>
        <select class="form-select @error('shipping_address_id') is-invalid @enderror" id="shipping_address_id" name="shipping_address_id">
            <option value="">Select Shipping Address</option>
            @foreach($customerAddresses as $address)
                <option value="{{ $address->address_id }}" {{ (old('shipping_address_id', $order->shipping_address_id ?? ($address->is_primary ? $address->address_id : '')) == $address->address_id) ? 'selected' : '' }}>
                    {{ $address->street_address_line_1 }}, {{ $address->city }} {{ $address->postal_code }} ({{ $address->address_type ?? 'Address' }})
                </option>
            @endforeach
        </select>
        @error('shipping_address_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label for="billing_address_id" class="form-label">Billing Address</label>
        <select class="form-select @error('billing_address_id') is-invalid @enderror" id="billing_address_id" name="billing_address_id">
            <option value="">Select Billing Address</option>
             @foreach($customerAddresses as $address)
                <option value="{{ $address->address_id }}" {{ (old('billing_address_id', $order->billing_address_id ?? ($address->is_primary ? $address->address_id : '')) == $address->address_id) ? 'selected' : '' }}>
                    {{ $address->street_address_line_1 }}, {{ $address->city }} {{ $address->postal_code }} ({{ $address->address_type ?? 'Address' }})
                </option>
            @endforeach
        </select>
        @error('billing_address_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<hr>
<h4>Line Items</h4>
<div id="order-items-container">
    @php
        $orderItems = old('items', isset($order) ? $order->items->toArray() : [['product_id' => '', 'item_name' => '', 'item_description' => '', 'quantity' => 1, 'unit_price' => '']]);
        $itemIndex = 0;
    @endphp

    @foreach($orderItems as $index => $item)
        @php $itemIndex = $index; @endphp
        <div class="row align-items-center mb-2 order-item-row border p-2 rounded">
            <input type="hidden" name="items[{{ $index }}][order_item_id]" value="{{ $item['order_item_id'] ?? '' }}">
            <div class="col-md-3 mb-2">
                <label class="form-label">Product/Service</label>
                <select name="items[{{ $index }}][product_id]" class="form-select product-select">
                    <option value="">Select Product</option>
                    @foreach($products as $product)
                        <option value="{{ $product->product_id }}" data-price="{{ $product->price }}" data-description="{{ $product->description }}" {{ ($item['product_id'] ?? '') == $product->product_id ? 'selected' : '' }}>
                            {{ $product->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 mb-2">
                <label class="form-label">Item Name <span class="text-danger">*</span></label>
                <input type="text" name="items[{{ $index }}][item_name]" class="form-control item-name" value="{{ $item['item_name'] ?? '' }}" required>
            </div>
            <div class="col-md-3 mb-2">
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
    <div class="col-md-4">
        <div class="row mb-2">
            <label for="discount_type" class="col-sm-4 col-form-label">Discount Type</label>
            <div class="col-sm-8">
                <select name="discount_type" id="discount_type" class="form-select @error('discount_type') is-invalid @enderror">
                    <option value="">None</option>
                    <option value="percentage" {{ old('discount_type', $order->discount_type ?? '') == 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                    <option value="fixed" {{ old('discount_type', $order->discount_type ?? '') == 'fixed' ? 'selected' : '' }}>Fixed Amount ($)</option>
                </select>
                @error('discount_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="row mb-2">
            <label for="discount_value" class="col-sm-4 col-form-label">Discount Value</label>
            <div class="col-sm-8">
                <input type="number" step="0.01" name="discount_value" id="discount_value" class="form-control @error('discount_value') is-invalid @enderror" value="{{ old('discount_value', $order->discount_value ?? 0) }}">
                @error('discount_value') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="row mb-2">
            <label for="tax_percentage" class="col-sm-4 col-form-label">Tax (%)</label>
            <div class="col-sm-8">
                <input type="number" step="0.01" name="tax_percentage" id="tax_percentage" class="form-control @error('tax_percentage') is-invalid @enderror" value="{{ old('tax_percentage', $order->tax_percentage ?? 0) }}">
                @error('tax_percentage') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>
</div>

<div class="mb-3">
    <label for="notes" class="form-label">Notes</label>
    <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes', $order->notes ?? '') }}</textarea>
    @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mt-4">
    <button type="submit" class="btn btn-primary">{{ isset($order->order_id) ? 'Update Order' : 'Create Order' }}</button>
    <a href="{{ route('orders.index') }}" class="btn btn-secondary">Cancel</a>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const itemsContainer = document.getElementById('order-items-container');
    const addItemBtn = document.getElementById('add-item-btn');
    let itemRowIndex = {{ $itemIndex + 1 }};
    const customerSelect = document.getElementById('customer_id');
    const shippingAddressSelect = document.getElementById('shipping_address_id');
    const billingAddressSelect = document.getElementById('billing_address_id');

    function populateAddressDropdown(selectElement, addresses, selectedId) {
        selectElement.innerHTML = '<option value="">Select Address</option>'; // Clear existing
        addresses.forEach(address => {
            const option = document.createElement('option');
            option.value = address.address_id;
            option.textContent = `${address.street_address_line_1}, ${address.city} ${address.postal_code} (${address.address_type || 'Address'})`;
            if (selectedId == address.address_id || (!selectedId && address.is_primary)) {
                option.selected = true;
            }
            selectElement.appendChild(option);
        });
    }

    customerSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const addresses = selectedOption.dataset.addresses ? JSON.parse(selectedOption.dataset.addresses) : [];
        populateAddressDropdown(shippingAddressSelect, addresses, null);
        populateAddressDropdown(billingAddressSelect, addresses, null);
    });

    addItemBtn.addEventListener('click', function () {
        const newRow = document.createElement('div');
        newRow.classList.add('row', 'align-items-center', 'mb-2', 'order-item-row', 'border', 'p-2', 'rounded');
        newRow.innerHTML = `
            <input type="hidden" name="items[${itemRowIndex}][order_item_id]" value="">
            <div class="col-md-3 mb-2"><label class="form-label">Product/Service</label><select name="items[${itemRowIndex}][product_id]" class="form-select product-select"><option value="">Select Product</option>@foreach($products as $product)<option value="{{ $product->product_id }}" data-price="{{ $product->price }}" data-description="{{ $product->description }}">{{ $product->name }}</option>@endforeach</select></div>
            <div class="col-md-3 mb-2"><label class="form-label">Item Name <span class="text-danger">*</span></label><input type="text" name="items[${itemRowIndex}][item_name]" class="form-control item-name" required></div>
            <div class="col-md-3 mb-2"><label class="form-label">Quantity <span class="text-danger">*</span></label><input type="number" name="items[${itemRowIndex}][quantity]" class="form-control item-quantity" value="1" min="1" required></div>
            <div class="col-md-2 mb-2"><label class="form-label">Unit Price <span class="text-danger">*</span></label><input type="number" step="0.01" name="items[${itemRowIndex}][unit_price]" class="form-control item-unit-price" min="0" required></div>
            <div class="col-md-1 mb-2 d-flex align-items-end"><button type="button" class="btn btn-danger remove-item-btn">&times;</button></div>
            <div class="col-md-12 mb-2"><label class="form-label">Description</label><textarea name="items[${itemRowIndex}][item_description]" class="form-control item-description" rows="1"></textarea></div>
        `;
        itemsContainer.appendChild(newRow);
        itemRowIndex++;
    });

    itemsContainer.addEventListener('click', function (event) {
        if (event.target.classList.contains('remove-item-btn')) {
            event.target.closest('.order-item-row').remove();
        }
    });

    itemsContainer.addEventListener('change', function(event) {
        if (event.target.classList.contains('product-select')) {
            const selectedOption = event.target.options[event.target.selectedIndex];
            const row = event.target.closest('.order-item-row');
            if (selectedOption.value) {
                row.querySelector('.item-name').value = selectedOption.text.trim();
                row.querySelector('.item-unit-price').value = selectedOption.dataset.price || '';
                row.querySelector('.item-description').value = selectedOption.dataset.description || '';
            }
        }
    });
     // Trigger change on page load if customer is pre-selected (e.g. on edit form)
    if(customerSelect.value){
        customerSelect.dispatchEvent(new Event('change'));
        // For edit, re-select previously saved addresses
        const currentShippingId = "{{ old('shipping_address_id', $order->shipping_address_id ?? '') }}";
        const currentBillingId = "{{ old('billing_address_id', $order->billing_address_id ?? '') }}";
        if(currentShippingId) shippingAddressSelect.value = currentShippingId;
        if(currentBillingId) billingAddressSelect.value = currentBillingId;
    }
});
</script>
@endpush