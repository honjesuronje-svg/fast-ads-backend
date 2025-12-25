@extends('layouts.app')

@section('page_title', 'Edit Tenant')

@section('page_content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Edit Tenant</h3>
    </div>
    <form action="{{ route('tenants.update', $tenant) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="card-body">
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                       id="name" name="name" value="{{ old('name', $tenant->name) }}" required>
                @error('name')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="slug">Slug</label>
                <input type="text" class="form-control @error('slug') is-invalid @enderror" 
                       id="slug" name="slug" value="{{ old('slug', $tenant->slug) }}" required>
                @error('slug')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select class="form-control @error('status') is-invalid @enderror" 
                        id="status" name="status" required>
                    <option value="active" {{ old('status', $tenant->status) === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $tenant->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="suspended" {{ old('status', $tenant->status) === 'suspended' ? 'selected' : '' }}>Suspended</option>
                </select>
                @error('status')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="allowed_domains">Allowed Domains (Optional)</label>
                <input type="text" class="form-control @error('allowed_domains') is-invalid @enderror" 
                       id="allowed_domains" name="allowed_domains" 
                       value="{{ old('allowed_domains', is_array($tenant->allowed_domains) ? implode(', ', $tenant->allowed_domains) : $tenant->allowed_domains) }}" 
                       placeholder="e.g., wkkworld.com, api.wkkworld.com (comma-separated)">
                <small class="form-text text-muted">Comma-separated list of allowed domains for CORS</small>
                @error('allowed_domains')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="rate_limit_per_minute">Rate Limit Per Minute (Optional)</label>
                <input type="number" class="form-control @error('rate_limit_per_minute') is-invalid @enderror" 
                       id="rate_limit_per_minute" name="rate_limit_per_minute" 
                       value="{{ old('rate_limit_per_minute', $tenant->rate_limit_per_minute) }}" 
                       min="1" max="10000">
                <small class="form-text text-muted">Default: 1000 requests per minute</small>
                @error('rate_limit_per_minute')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Update Tenant</button>
            <a href="{{ route('tenants.index') }}" class="btn btn-default">Cancel</a>
        </div>
    </form>
</div>
@endsection

