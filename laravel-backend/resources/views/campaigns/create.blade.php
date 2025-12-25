@extends('layouts.app')

@section('page_title', 'Create Campaign')

@section('page_content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Create New Campaign</h3>
    </div>
    <form action="{{ route('campaigns.store') }}" method="POST">
        @csrf
        <div class="card-body">
            <div class="form-group">
                <label for="tenant_id">Tenant</label>
                <select class="form-control @error('tenant_id') is-invalid @enderror" 
                        id="tenant_id" name="tenant_id" required>
                    <option value="">Select Tenant</option>
                    @foreach($tenants as $tenant)
                        <option value="{{ $tenant->id }}" {{ old('tenant_id') == $tenant->id ? 'selected' : '' }}>
                            {{ $tenant->name }}
                        </option>
                    @endforeach
                </select>
                @error('tenant_id')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                       id="name" name="name" value="{{ old('name') }}" required>
                @error('name')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea class="form-control @error('description') is-invalid @enderror" 
                          id="description" name="description" rows="3">{{ old('description') }}</textarea>
                @error('description')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="start_date">Start Date</label>
                <input type="date" class="form-control @error('start_date') is-invalid @enderror" 
                       id="start_date" name="start_date" value="{{ old('start_date') }}" required>
                @error('start_date')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="end_date">End Date</label>
                <input type="date" class="form-control @error('end_date') is-invalid @enderror" 
                       id="end_date" name="end_date" value="{{ old('end_date') }}" required>
                @error('end_date')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="budget">Budget (Optional)</label>
                <input type="number" class="form-control @error('budget') is-invalid @enderror" 
                       id="budget" name="budget" value="{{ old('budget') }}" min="0" step="0.01">
                @error('budget')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select class="form-control @error('status') is-invalid @enderror" 
                        id="status" name="status" required>
                    <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                @error('status')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Create Campaign</button>
            <a href="{{ route('campaigns.index') }}" class="btn btn-default">Cancel</a>
        </div>
    </form>
</div>
@endsection

