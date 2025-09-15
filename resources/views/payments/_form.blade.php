@csrf
<x-tenant-hidden :model="$payable ?? null" />

{{-- Hidden fields for payable_id and payable_type --}}
<input type="hidden" name="payable_id" value="{{ $payable->getKey() }}">
<input type="hidden" name="payable_type" value="{{ get_class($payable) }}">

<div class="row">
    <div class="col-md-4 mb-3">
        <label for="amount" class="form-label">Amount</label>
        <div class="input-group">
            <span class="input-group-text">$</span>
            <input type="number" name="amount" id="amount" class="form-control @error('amount') is-invalid @enderror" step="0.01" max="{{ $payable->amount_due }}" value="{{ old('amount', number_format($payable->amount_due, 2, '.', '')) }}" required>
        </div>
        @error('amount')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="payment_date" class="form-label">Payment Date</label>
        <input type="date" name="payment_date" id="payment_date" class="form-control @error('payment_date') is-invalid @enderror" value="{{ old('payment_date', now()->toDateString()) }}" required>
         @error('payment_date')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="payment_method" class="form-label">Payment Method</label>
        <select name="payment_method" id="payment_method" class="form-select @error('payment_method') is-invalid @enderror" required>
            <option value="Bank Transfer" @selected(old('payment_method') == 'Bank Transfer')>Bank Transfer</option>
            <option value="Cash" @selected(old('payment_method') == 'Cash')>Cash</option>
            <option value="Credit Card" @selected(old('payment_method') == 'Credit Card')>Credit Card</option>
            <option value="Cheque" @selected(old('payment_method') == 'Cheque')>Cheque</option>
        </select>
         @error('payment_method')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-12 mb-3">
        <label for="reference_number" class="form-label">Reference Number</label>
        <input type="text" name="reference_number" id="reference_number" class="form-control @error('reference_number') is-invalid @enderror" value="{{ old('reference_number') }}">
         @error('reference_number')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<button type="submit" class="btn btn-primary">Record Payment</button>