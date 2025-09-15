@csrf
<x-tenant-hidden :model="$journalEntry ?? null" />
<div class="row">
    <div class="col-md-4 mb-3">
        <label for="entry_date" class="form-label">Entry Date <span class="text-danger">*</span></label>
        <input type="date" class="form-control @error('entry_date') is-invalid @enderror" id="entry_date" name="entry_date" value="{{ old('entry_date', ($journalEntry->entry_date ?? now())->format('Y-m-d')) }}" required>
        @error('entry_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4 mb-3">
        <label for="transaction_type" class="form-label">Transaction Type <span class="text-danger">*</span></label>
        <select class="form-select @error('transaction_type') is-invalid @enderror" id="transaction_type" name="transaction_type" required>
            <option value="">Select Type</option>
            @foreach($manualTransactionTypes as $type)
                <option value="{{ $type }}" {{ old('transaction_type', $journalEntry->transaction_type ?? '') == $type ? 'selected' : '' }}>{{ $type }}</option>
            @endforeach
            {{-- Add other types if needed, or make it a text input if types are very dynamic --}}
            @if(isset($journalEntry) && $journalEntry->transaction_type && !in_array($journalEntry->transaction_type, $manualTransactionTypes))
                <option value="{{ $journalEntry->transaction_type }}" selected>{{ $journalEntry->transaction_type }} (Existing)</option>
            @endif
        </select>
        @error('transaction_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4 mb-3">
        <label for="description" class="form-label">Description</label>
        <input type="text" class="form-control @error('description') is-invalid @enderror" id="description" name="description" value="{{ old('description', $journalEntry->description ?? '') }}">
        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<hr>
<h4>Entry Lines</h4>
@error('lines') <div class="alert alert-danger">{{ $message }}</div> @enderror

<div id="journal-lines-container">
    @php
        $currentLines = old('lines', (isset($journalEntry) && $journalEntry->lines->isNotEmpty() ? $journalEntry->lines->toArray() : [['account_name' => '', 'debit_amount' => '', 'credit_amount' => ''], ['account_name' => '', 'debit_amount' => '', 'credit_amount' => '']]));
        $lineIndex = 0;
    @endphp

    @foreach($currentLines as $index => $line)
        @php $lineIndex = $index; @endphp
        <div class="row align-items-center mb-2 journal-line-row border p-2 rounded">
            <div class="col-md-5 mb-2">
                <label class="form-label">Account <span class="text-danger">*</span></label>
                <select name="lines[{{ $index }}][account_code]" class="form-select @error('lines.'.$index.'.account_code') is-invalid @enderror" required>
                    <option value="">Select account</option>
                    @php
                        $accounts = \App\Models\Account::orderBy('code')->get();
                    @endphp
                    @foreach($accounts as $account)
                        <option value="{{ $account->code }}" {{ (isset($line['account_code']) && $line['account_code'] == $account->code) ? 'selected' : '' }}>
                            {{ $account->code }} - {{ $account->description }}
                        </option>
                    @endforeach
                </select>
                @error('lines.'.$index.'.account_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-3 mb-2">
                <label class="form-label">Debit</label>
                <input type="number" step="0.01" name="lines[{{ $index }}][debit_amount]" class="form-control debit-input @error('lines.'.$index.'.debit_amount') is-invalid @enderror" value="{{ $line['debit_amount'] ?? '' }}" placeholder="0.00">
                @error('lines.'.$index.'.debit_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-3 mb-2">
                <label class="form-label">Credit</label>
                <input type="number" step="0.01" name="lines[{{ $index }}][credit_amount]" class="form-control credit-input @error('lines.'.$index.'.credit_amount') is-invalid @enderror" value="{{ $line['credit_amount'] ?? '' }}" placeholder="0.00">
                @error('lines.'.$index.'.credit_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-1 mb-2 d-flex align-items-end">
                <button type="button" class="btn btn-danger remove-line-btn">&times;</button>
            </div>
            {{-- Add entity_id and entity_type fields here if needed for sub-ledger --}}
        </div>
    @endforeach
</div>
<button type="button" id="add-line-btn" class="btn btn-success btn-sm mt-2">Add Line</button>

<div class="row mt-3">
    <div class="col-md-5 offset-md-5">
        <div class="d-flex justify-content-between">
            <strong>Total Debits:</strong> <span id="total-debits">$0.00</span>
        </div>
        <div class="d-flex justify-content-between">
            <strong>Total Credits:</strong> <span id="total-credits">$0.00</span>
        </div>
         <div class="d-flex justify-content-between mt-1" id="balance-status">
            <strong>Balance:</strong> <span class="text-danger">Out of Balance</span>
        </div>
    </div>
</div>

<div class="mt-4">
    <button type="submit" class="btn btn-primary">{{ isset($journalEntry->journal_entry_id) && $journalEntry->journal_entry_id ? 'Update Journal Entry' : 'Create Journal Entry' }}</button>
    <a href="{{ route('journal-entries.index') }}" class="btn btn-secondary">Cancel</a>
</div>

@push('scripts')
    @include('journal_entries._form_scripts')
@endpush