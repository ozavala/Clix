@csrf
<div class="row">
    <div class="col-md-8 mb-3">
        <label for="name" class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $product->name ?? '') }}" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4 mb-3">
        <label for="sku" class="form-label">{{ __('SKU') }} ({{ __('Stock Keeping Unit') }})</label>
        <input type="text" class="form-control @error('sku') is-invalid @enderror" id="sku" name="sku" value="{{ old('sku', $product->sku ?? '') }}">
        @error('sku') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mb-3">
    <label for="description" class="form-label">{{ __('Description') }}</label>
    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $product->description ?? '') }}</textarea>
    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="category_id" class="form-label">{{ __('Category') }}</label>
    <select class="form-select @error('category_id') is-invalid @enderror" id="category_id" name="category_id">
        <option value="">{{ __('Select Category') }}</option>
        @foreach($categories as $category)
            <option value="{{ $category->category_id }}" {{ old('category_id', $product->category_id ?? '') == $category->category_id ? 'selected' : '' }}>{{ $category->name }}</option>
        @endforeach
    </select>
    @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label for="price" class="form-label">{{ __('Price') }} ($) <span class="text-danger">*</span></label>
        <input type="number" step="0.01" class="form-control @error('price') is-invalid @enderror" id="price" name="price" value="{{ old('price', isset($product->price) ? number_format($product->price, 2, '.', '') : '') }}" required>
        @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4 mb-3">
        <label for="cost" class="form-label">{{ __('Cost') }} ($) ({{ __('Optional') }})</label>
        <input type="number" step="0.01" class="form-control @error('cost') is-invalid @enderror" id="cost" name="cost" value="{{ old('cost', isset($product->cost) ? number_format($product->cost, 2, '.', '') : '') }}">
        @error('cost') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label for="is_service" class="form-label">{{ __('Type') }} <span class="text-danger">*</span></label>
        <select class="form-select @error('is_service') is-invalid @enderror" id="is_service" name="is_service" required>
            <option value="0" {{ old('is_service', $product->is_service ?? '0') == '0' ? 'selected' : '' }}>{{ __('Product') }} ({{ __('Track Stock') }})</option>
            <option value="1" {{ old('is_service', $product->is_service ?? '0') == '1' ? 'selected' : '' }}>{{ __('Service') }} ({{ __('No Stock') }})</option>
        </select>
        @error('is_service') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4 mb-3" id="quantity_on_hand_field" style="{{ old('is_service', $product->is_service ?? '0') == '1' ? 'display:none;' : '' }}">
        <label for="quantity_on_hand" class="form-label">{{ __('Quantity on Hand') }}</label>
        <input type="number" class="form-control @error('quantity_on_hand') is-invalid @enderror" id="quantity_on_hand" name="quantity_on_hand" value="{{ old('quantity_on_hand', $product->quantity_on_hand ?? 0) }}">
        @error('quantity_on_hand') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4 mb-3">
        <label for="is_active" class="form-label">{{ __('Status') }} <span class="text-danger">*</span></label>
        <select class="form-select @error('is_active') is-invalid @enderror" id="is_active" name="is_active" required>
            <option value="1" {{ old('is_active', $product->is_active ?? '1') == '1' ? 'selected' : '' }}>{{ __('Active') }}</option>
            <option value="0" {{ old('is_active', $product->is_active ?? '1') == '0' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
        </select>
        @error('is_active') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<hr class="my-4">

<h4>{{ __('Product Features') }}</h4>
<div id="product-features-container">
    @php
        $existingFeatures = old('features', isset($product) ? $product->features->mapWithKeys(function ($feature, $key) {
            return [ $key => ['feature_id' => $feature->feature_id, 'value' => $feature->pivot->value] ];
        })->all() : []);
        $featureIndex = 0;
    @endphp

    @if(!empty($existingFeatures))
        @foreach($existingFeatures as $index => $featureData)
            @php $featureIndex = $index; @endphp
            <div class="row align-items-end mb-2 product-feature-row">
                <div class="col-md-5">
                    <label class="form-label">{{ __('Feature') }}</label>
                    <select name="features[{{ $index }}][feature_id]" class="form-select">
                        <option value="">{{ __('Select Feature') }}</option>
                        @foreach($productFeatures as $pf)
                            <option value="{{ $pf->feature_id }}" {{ ($featureData['feature_id'] ?? '') == $pf->feature_id ? 'selected' : '' }}>{{ $pf->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">{{ __('Value') }}</label>
                    <input type="text" name="features[{{ $index }}][value]" class="form-control" value="{{ $featureData['value'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger remove-feature-btn">{{ __('Remove') }}</button>
                </div>
            </div>
        @endforeach
    @endif
</div>
<button type="button" id="add-feature-btn" class="btn btn-success btn-sm mt-2">{{ __('Add Feature') }}</button>

@error('features') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
@foreach ($errors->get('features.*') as $message)
    <div class="text-danger small mt-1">{{ $message[0] }}</div>
@endforeach

<hr class="my-4">

<h4>{{ __('Tax Configuration') }}</h4>
<div class="row">
    <div class="col-md-4 mb-3">
        <label for="is_taxable" class="form-label">{{ __('Pays Tax?') }}</label>
        <select class="form-select @error('is_taxable') is-invalid @enderror" id="is_taxable" name="is_taxable">
            <option value="1" {{ old('is_taxable', $product->is_taxable ?? '1') == '1' ? 'selected' : '' }}>{{ __('Yes') }}</option>
            <option value="0" {{ old('is_taxable', $product->is_taxable ?? '1') == '0' ? 'selected' : '' }}>{{ __('No') }}</option>
        </select>
        @error('is_taxable') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4 mb-3">
        <label for="tax_rate_percentage" class="form-label">{{ __('Tax Rate') }} (%)</label>
        <input type="number" step="0.01" min="0" max="100" class="form-control @error('tax_rate_percentage') is-invalid @enderror" 
               id="tax_rate_percentage" name="tax_rate_percentage" 
               value="{{ old('tax_rate_percentage', $product->tax_rate_percentage ?? '') }}" 
               placeholder="{{ __('Ex: 15.00') }}">
        @error('tax_rate_percentage') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4 mb-3">
        <label for="tax_category" class="form-label">{{ __('Tax Category') }}</label>
        <select class="form-select @error('tax_category') is-invalid @enderror" id="tax_category" name="tax_category">
            <option value="">{{ __('Select Category') }}</option>
            <option value="goods" {{ old('tax_category', $product->tax_category ?? '') == 'goods' ? 'selected' : '' }}>{{ __('Goods') }}</option>
            <option value="services" {{ old('tax_category', $product->tax_category ?? '') == 'services' ? 'selected' : '' }}>{{ __('Services') }}</option>
            <option value="transport" {{ old('tax_category', $product->tax_category ?? '') == 'transport' ? 'selected' : '' }}>{{ __('Transport') }}</option>
            <option value="insurance" {{ old('tax_category', $product->tax_category ?? '') == 'insurance' ? 'selected' : '' }}>{{ __('Insurance') }}</option>
            <option value="storage" {{ old('tax_category', $product->tax_category ?? '') == 'storage' ? 'selected' : '' }}>{{ __('Storage') }}</option>
            <option value="transport_public" {{ old('tax_category', $product->tax_category ?? '') == 'transport_public' ? 'selected' : '' }}>{{ __('Public Transport') }}</option>
        </select>
        @error('tax_category') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label for="tax_country_code" class="form-label">{{ __('Country for Tax') }}</label>
        <select class="form-select @error('tax_country_code') is-invalid @enderror" id="tax_country_code" name="tax_country_code">
            <option value="EC" {{ old('tax_country_code', $product->tax_country_code ?? 'EC') == 'EC' ? 'selected' : '' }}>{{ __('Ecuador') }}</option>
            <option value="ES" {{ old('tax_country_code', $product->tax_country_code ?? 'EC') == 'ES' ? 'selected' : '' }}>{{ __('Spain') }}</option>
            <option value="MX" {{ old('tax_country_code', $product->tax_country_code ?? 'EC') == 'MX' ? 'selected' : '' }}>{{ __('Mexico') }}</option>
        </select>
        @error('tax_country_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-8 mb-3">
        <label for="tax_rate_id" class="form-label">{{ __('Tax Rate') }} ({{ __('Model') }})</label>
        <select class="form-select @error('tax_rate_id') is-invalid @enderror" id="tax_rate_id" name="tax_rate_id">
            <option value="">{{ __('Select Rate') }}</option>
            @foreach($taxRates ?? [] as $taxRate)
                <option value="{{ $taxRate->tax_rate_id }}" 
                        {{ old('tax_rate_id', $product->tax_rate_id ?? '') == $taxRate->tax_rate_id ? 'selected' : '' }}>
                    {{ $taxRate->name }} ({{ $taxRate->rate }}%)
                </option>
            @endforeach
        </select>
        <small class="form-text text-muted">{{ __('Optional: use model rate instead of specific rate') }}</small>
        @error('tax_rate_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<hr class="my-4">

<div id="inventory-management-section" style="{{ old('is_service', $product->is_service ?? '0') == '1' ? 'display:none;' : '' }}">
    <h4>{{ __('Inventory Levels per Warehouse') }}</h4>
    @if(isset($warehouses) && $warehouses->count() > 0)
        @foreach($warehouses as $warehouse)
            @php
                $currentQuantity = old('inventory.'.$warehouse->warehouse_id.'.quantity', $product->warehouses->find($warehouse->warehouse_id)->pivot->quantity ?? 0);
            @endphp
            <div class="row mb-2 align-items-center">
                <label for="inventory_{{ $warehouse->warehouse_id }}" class="col-md-3 col-form-label">{{ $warehouse->name }}</label>
                <div class="col-md-3">
                    <input type="number" class="form-control @error('inventory.'.$warehouse->warehouse_id.'.quantity') is-invalid @enderror"
                           id="inventory_{{ $warehouse->warehouse_id }}"
                           name="inventory[{{ $warehouse->warehouse_id }}][quantity]"
                           value="{{ $currentQuantity }}" min="0">
                    @error('inventory.'.$warehouse->warehouse_id.'.quantity')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        @endforeach
    @else
        <p>{{ __('No active warehouses found.') }} <a href="{{ route('warehouses.create') }}">{{ __('Create a warehouse') }}</a> {{ __('to manage inventory.') }}</p>
    @endif
    <hr class="my-4">
</div>

<div class="mt-4">
    <button type="submit" class="btn btn-primary">{{ isset($product->product_id) ? __('Update') : __('Create') }} {{ __('Product/Service') }}</button>
    <a href="{{ route('products.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const typeSelect = document.getElementById('is_service');
        const quantityField = document.getElementById('quantity_on_hand_field');
        const quantityInput = document.getElementById('quantity_on_hand');
        const inventoryManagementSection = document.getElementById('inventory-management-section');
        let featureRowIndex = {{ $featureIndex + 1 }}; // Start index for new rows

        function toggleQuantityField() {
            if (typeSelect.value === '1') { // Service
                quantityField.style.display = 'none';
                if (inventoryManagementSection) inventoryManagementSection.style.display = 'none';
                quantityInput.value = 0; // Or clear it, or set to null if your backend handles it
            } else { // Product
                quantityField.style.display = 'block';
                if (inventoryManagementSection) inventoryManagementSection.style.display = 'block';
            }
        }

        typeSelect.addEventListener('change', toggleQuantityField);
        // Initial check in case of old input or editing
        toggleQuantityField();

        // Product Features
        const featuresContainer = document.getElementById('product-features-container');
        const addFeatureBtn = document.getElementById('add-feature-btn');

        addFeatureBtn.addEventListener('click', function() {
            const newRow = document.createElement('div');
            newRow.classList.add('row', 'align-items-end', 'mb-2', 'product-feature-row');
            newRow.innerHTML = `
                <div class="col-md-5">
                    <label class="form-label">{{ __('Feature') }}</label>
                    <select name="features[${featureRowIndex}][feature_id]" class="form-select">
                        <option value="">{{ __('Select Feature') }}</option>
                        @foreach($productFeatures as $pf)
                            <option value="{{ $pf->feature_id }}">{{ $pf->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">{{ __('Value') }}</label>
                    <input type="text" name="features[${featureRowIndex}][value]" class="form-control">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger remove-feature-btn">{{ __('Remove') }}</button>
                </div>
            `;
            featuresContainer.appendChild(newRow);
            featureRowIndex++;
        });

        featuresContainer.addEventListener('click', function(event) {
            if (event.target.classList.contains('remove-feature-btn')) {
                event.target.closest('.product-feature-row').remove();
            }
        });
    });
</script>
@endpush