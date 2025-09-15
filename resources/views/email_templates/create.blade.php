@extends('layouts.app')

@section('title', 'Create Email Template')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Create Email Template</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('email-templates.store') }}" method="POST">
                        @csrf
                        <x-tenant-hidden />
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Template Name *</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name') }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="type">Template Type *</label>
                                    <select class="form-control @error('type') is-invalid @enderror" 
                                            id="type" name="type" required>
                                        <option value="">Select Type</option>
                                        <option value="newsletter" {{ old('type') == 'newsletter' ? 'selected' : '' }}>Newsletter</option>
                                        <option value="promotional" {{ old('type') == 'promotional' ? 'selected' : '' }}>Promotional</option>
                                        <option value="welcome" {{ old('type') == 'welcome' ? 'selected' : '' }}>Welcome</option>
                                        <option value="notification" {{ old('type') == 'notification' ? 'selected' : '' }}>Notification</option>
                                        <option value="custom" {{ old('type') == 'custom' ? 'selected' : '' }}>Custom</option>
                                    </select>
                                    @error('type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
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
                                      id="content" name="content" rows="15" required>{{ old('content') }}</textarea>
                            @error('content')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Available variables: {!! "{{recipient_name}}" !!}, {!! "{{recipient_email}}" !!}, {!! "{{campaign_name}}" !!}, {!! "{{company_name}}" !!}, {!! "{{unsubscribe_url}}" !!}
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="html_content">HTML Content (Optional)</label>
                            <textarea class="form-control @error('html_content') is-invalid @enderror" 
                                      id="html_content" name="html_content" rows="15">{{ old('html_content') }}</textarea>
                            @error('html_content')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Leave empty to use plain text content</small>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Active
                            </label>
                        </div>

                        <div class="form-group mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Create Template
                            </button>
                            <a href="{{ route('email-templates.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 