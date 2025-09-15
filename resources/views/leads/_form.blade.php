@csrf
<x-tenant-hidden :model="$lead ?? null" />
<div class="row">
    <div class="col-md-8 mb-3">
        <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $lead->title ?? '') }}" required>
        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4 mb-3">
        <label for="value" class="form-label">Potential Value ($)</label>
        <input type="number" step="0.01" class="form-control @error('value') is-invalid @enderror" id="value" name="value" value="{{ old('value', isset($lead->value) ? number_format($lead->value, 2, '.', '') : '') }}">
        @error('value') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mb-3">
    <label for="description" class="form-label">Description</label>
    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $lead->description ?? '') }}</textarea>
    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
            @foreach($statuses as $key => $label)
                <option value="{{ $key }}" {{ old('status', $lead->status ?? 'New') == $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label for="source" class="form-label">Source</label>
        <select class="form-select @error('source') is-invalid @enderror" id="source" name="source">
            <option value="">Select Source</option>
            @foreach($sources as $key => $label)
                <option value="{{ $key }}" {{ old('source', $lead->source ?? '') == $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @error('source') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="customer_id" class="form-label">Customer (Optional)</label>
        <select class="form-select @error('customer_id') is-invalid @enderror" id="customer_id" name="customer_id">
            <option value="">None / New Contact</option>
            @foreach($customers as $customer)
                <option value="{{ $customer->customer_id }}" {{ old('customer_id', $lead->customer_id ?? '') == $customer->customer_id ? 'selected' : '' }}>
                    {{ $customer->full_name }} {{ $customer->company_name ? '('.$customer->company_name.')' : '' }}
                </option>
            @endforeach
        </select>
        @error('customer_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label for="assigned_to_user_id" class="form-label">Assigned To</label>
        <select class="form-select @error('assigned_to_user_id') is-invalid @enderror" id="assigned_to_user_id" name="assigned_to_user_id">
            <option value="">Unassigned</option>
            @foreach($crmUsers as $user)
                <option value="{{ $user->user_id }}" {{ old('assigned_to_user_id', $lead->assigned_to_user_id ?? '') == $user->user_id ? 'selected' : '' }}>
                    {{ $user->full_name }} ({{ $user->username }})
                </option>
            @endforeach
        </select>
        @error('assigned_to_user_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<fieldset class="mb-3 border p-3">
    <legend class="w-auto px-2 h6">Contact Information (if not existing customer)</legend>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="contact_name" class="form-label">Contact Name</label>
            <input type="text" class="form-control @error('contact_name') is-invalid @enderror" id="contact_name" name="contact_name" value="{{ old('contact_name', $lead->contact_name ?? '') }}">
            @error('contact_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4 mb-3">
            <label for="contact_email" class="form-label">Contact Email</label>
            <input type="email" class="form-control @error('contact_email') is-invalid @enderror" id="contact_email" name="contact_email" value="{{ old('contact_email', $lead->contact_email ?? '') }}">
            @error('contact_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4 mb-3">
            <label for="contact_phone" class="form-label">Contact Phone</label>
            <input type="tel" class="form-control @error('contact_phone') is-invalid @enderror" id="contact_phone" name="contact_phone" value="{{ old('contact_phone', $lead->contact_phone ?? '') }}">
            @error('contact_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
</fieldset>

<div class="mb-3">
    <label for="expected_close_date" class="form-label">Expected Close Date</label>
    <input type="date" class="form-control @error('expected_close_date') is-invalid @enderror" id="expected_close_date" name="expected_close_date" value="{{ old('expected_close_date', isset($lead->expected_close_date) ? $lead->expected_close_date->format('Y-m-d') : '') }}">
    @error('expected_close_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mt-4">
    <button type="submit" class="btn btn-primary">{{ isset($lead) ? 'Update Lead' : 'Create Lead' }}</button>
    <a href="{{ route('leads.index') }}" class="btn btn-secondary">Cancel</a>
</div>