@extends('layouts.app')

@section('title', 'Create Marketing Campaign')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Create Marketing Campaign</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('marketing-campaigns.store') }}" method="POST">
                        @csrf
                        <x-tenant-hidden />
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Campaign Name *</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name') }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="type">Campaign Type *</label>
                                    <select class="form-control @error('type') is-invalid @enderror" 
                                            id="type" name="type" required>
                                        <option value="">Select Type</option>
                                        <option value="email" {{ old('type') == 'email' ? 'selected' : '' }}>Email</option>
                                        <option value="newsletter" {{ old('type') == 'newsletter' ? 'selected' : '' }}>Newsletter</option>
                                        <option value="promotional" {{ old('type') == 'promotional' ? 'selected' : '' }}>Promotional</option>
                                        <option value="announcement" {{ old('type') == 'announcement' ? 'selected' : '' }}>Announcement</option>
                                    </select>
                                    @error('type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email_template_id">Email Template</label>
                                    <select class="form-control @error('email_template_id') is-invalid @enderror" 
                                            id="email_template_id" name="email_template_id">
                                        <option value="">Select Template (Optional)</option>
                                        @foreach($templates as $template)
                                            <option value="{{ $template->id }}" {{ old('email_template_id') == $template->id ? 'selected' : '' }}>
                                                {{ $template->name }} ({{ ucfirst($template->type) }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('email_template_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="scheduled_at">Schedule Campaign</label>
                                    <input type="datetime-local" class="form-control @error('scheduled_at') is-invalid @enderror" 
                                           id="scheduled_at" name="scheduled_at" value="{{ old('scheduled_at') }}">
                                    @error('scheduled_at')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Leave empty to save as draft</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="subject">Email Subject *</label>
                            <input type="text" class="form-control @error('subject') is-invalid @enderror" 
                                   id="subject" name="subject" value="{{ old('subject') }}" required>
                            @error('subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="content">Email Content *</label>
                            <textarea class="form-control @error('content') is-invalid @enderror" 
                                      id="content" name="content" rows="10" required>{{ old('content') }}</textarea>
                            @error('content')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Available variables: {!! "{{recipient_name}}" !!}, {!! "{{recipient_email}}" !!}, {!! "{{campaign_name}}" !!}, {!! "{{company_name}}" !!}, {!! "{{unsubscribe_url}}" !!}
                            </small>
                        </div>

                        <div class="form-group">
                            <label>Target Audience</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="include_customers" name="target_audience[customers]" value="1" {{ old('target_audience.customers') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="include_customers">
                                            Include Customers
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="include_leads" name="target_audience[leads]" value="1" {{ old('target_audience.leads') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="include_leads">
                                            Include Leads
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Create Campaign
                            </button>
                            <a href="{{ route('marketing-campaigns.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-save draft functionality
    const form = document.querySelector('form');
    const inputs = form.querySelectorAll('input, textarea, select');
    
    inputs.forEach(input => {
        input.addEventListener('change', function() {
            localStorage.setItem('campaign_draft', JSON.stringify({
                name: document.getElementById('name').value,
                description: document.getElementById('description').value,
                type: document.getElementById('type').value,
                subject: document.getElementById('subject').value,
                content: document.getElementById('content').value,
                email_template_id: document.getElementById('email_template_id').value,
                scheduled_at: document.getElementById('scheduled_at').value
            }));
        });
    });

    // Load draft on page load
    const draft = localStorage.getItem('campaign_draft');
    if (draft && !document.getElementById('name').value) {
        const draftData = JSON.parse(draft);
        Object.keys(draftData).forEach(key => {
            const element = document.getElementById(key);
            if (element) {
                element.value = draftData[key];
            }
        });
    }

    // Clear draft on successful submission
    form.addEventListener('submit', function() {
        localStorage.removeItem('campaign_draft');
    });
});
</script>
@endpush
@endsection 