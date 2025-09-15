<x-tenant-hidden :model="$contact ?? null" />
<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <label for="contactable_type_selector" class="form-label">Associated With</label>
            <select class="form-select" id="contactable_type_selector">
                <option value="">Select Type</option>
                <option value="customer" {{ old('contactable_type', $contact->contactable_type) == \App\Models\Customer::class ? 'selected' : '' }}>Customer</option>
                <option value="supplier" {{ old('contactable_type', $contact->contactable_type) == \App\Models\Supplier::class ? 'selected' : '' }}>Supplier</option>
            </select>
        </div>

        <div class="mb-3" id="customer_select_group" style="display: none;">
            <label for="customer_id" class="form-label">Company <span class="text-danger">*</span></label>
            <select class="form-select @error('contactable_id') is-invalid @enderror" id="customer_id">
                  <option value="">Select a Customer</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->customer_id }}" {{ (old('contactable_type', $contact->contactable_type) == \App\Models\Customer::class && old('contactable_id', $contact->contactable_id) == $customer->customer_id) ? 'selected' : '' }}>
                        {{ $customer->company_name ?: $customer->full_name }}
                    </option>
                @endforeach
            </select>
           @error('contactable_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3" id="supplier_select_group" style="display: none;">
            <label for="supplier_id" class="form-label">Supplier <span class="text-danger">*</span></label>
            <select class="form-select @error('contactable_id') is-invalid @enderror" id="supplier_id">
                <option value="">Select a Supplier</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->supplier_id }}" {{ (old('contactable_type', $contact->contactable_type) == \App\Models\Supplier::class && old('contactable_id', $contact->contactable_id) == $supplier->supplier_id) ? 'selected' : '' }}>
                        {{ $supplier->name }}
                    </option>
                @endforeach
            </select>
            @error('contactable_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        {{-- Hidden fields to actually submit the polymorphic data --}}
        <input type="hidden" name="contactable_id" id="hidden_contactable_id" value="{{ old('contactable_id', $contact->contactable_id) }}">
        <input type="hidden" name="contactable_type" id="hidden_contactable_type" value="{{ old('contactable_type', $contact->contactable_type) }}">

        <div class="mb-3">
            <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('first_name') is-invalid @enderror" id="first_name" name="first_name" value="{{ old('first_name', $contact->first_name) }}" required>
            @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('last_name') is-invalid @enderror" id="last_name" name="last_name" value="{{ old('last_name', $contact->last_name) }}" required>
            @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
       
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label for="title" class="form-label">Title</label>
            <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $contact->title) }}">
            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $contact->email) }}">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label for="phone" class="form-label">Phone</label>
            <input type="tel" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $contact->phone) }}">
            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="mt-3">
    <button type="submit" class="btn btn-primary">{{ $contact->exists ? 'Update Contact' : 'Create Contact' }}</button>
    @php
        $cancelUrl = route('contacts.index'); // Default fallback
        if ($contact->exists) {
            if ($contact->contactable_id && $contact->contactable_type === \App\Models\Customer::class) {
                $cancelUrl = route('customers.show', $contact->contactable_id);
            } elseif ($contact->contactable_id && $contact->contactable_type === \App\Models\Supplier::class) {
                $cancelUrl = route('suppliers.show', $contact->contactable_id);
            }
        } else {
            if (request()->filled('customer_id')) {
                $cancelUrl = route('customers.show', request('customer_id'));
            } elseif (request()->filled('supplier_id')) {
                $cancelUrl = route('suppliers.show', request('supplier_id'));
            }
        }
    @endphp
    <a href="{{ $cancelUrl }}" class="btn btn-secondary">Cancel</a>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const contactableTypeSelector = document.getElementById('contactable_type_selector');
    const customerSelectGroup = document.getElementById('customer_select_group');
    const customerSelect = document.getElementById('customer_id');
    const supplierSelectGroup = document.getElementById('supplier_select_group');
    const supplierSelect = document.getElementById('supplier_id');
    const hiddenContactableId = document.getElementById('hidden_contactable_id');
    const hiddenContactableType = document.getElementById('hidden_contactable_type');

    function syncFormState() {
        const selectedType = contactableTypeSelector.value;

        customerSelectGroup.style.display = (selectedType === 'customer') ? 'block' : 'none';
        supplierSelectGroup.style.display = (selectedType === 'supplier') ? 'block' : 'none';
        customerSelect.required = (selectedType === 'customer');
        supplierSelect.required = (selectedType === 'supplier');

        hiddenContactableId.value = (selectedType === 'customer') ? customerSelect.value : ((selectedType === 'supplier') ? supplierSelect.value : '');
        hiddenContactableType.value = (selectedType === 'customer') ? '{{ addslashes(\App\Models\Customer::class) }}' : ((selectedType === 'supplier') ? '{{ addslashes(\App\Models\Supplier::class) }}' : '');
    }

    // Add event listeners to all relevant dropdowns to sync state on any change
    [contactableTypeSelector, customerSelect, supplierSelect].forEach(element => {
        element.addEventListener('change', syncFormState);
    });

    syncFormState(); // Call once on page load to set the initial state correctly
});
</script>
@endpush