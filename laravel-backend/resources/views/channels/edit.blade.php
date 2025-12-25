@extends('layouts.app')

@section('page_title', 'Edit Channel')

@section('page_content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Edit Channel</h3>
    </div>
    <form action="{{ route('channels.update', $channel) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="card-body">
            <div class="form-group">
                <label for="tenant_id">Tenant</label>
                <select class="form-control @error('tenant_id') is-invalid @enderror" 
                        id="tenant_id" name="tenant_id" required>
                    <option value="">Select Tenant</option>
                    @foreach($tenants as $tenant)
                        <option value="{{ $tenant->id }}" 
                                {{ old('tenant_id', $channel->tenant_id) == $tenant->id ? 'selected' : '' }}>
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
                       id="name" name="name" value="{{ old('name', $channel->name) }}" required>
                @error('name')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="slug">Slug</label>
                <input type="text" class="form-control @error('slug') is-invalid @enderror" 
                       id="slug" name="slug" value="{{ old('slug', $channel->slug) }}" required>
                @error('slug')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="hls_manifest_url">HLS Manifest URL</label>
                <input type="url" class="form-control @error('hls_manifest_url') is-invalid @enderror" 
                       id="hls_manifest_url" name="hls_manifest_url" value="{{ old('hls_manifest_url', $channel->hls_manifest_url) }}">
                @error('hls_manifest_url')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="ad_break_strategy">Ad Break Strategy</label>
                <select class="form-control @error('ad_break_strategy') is-invalid @enderror" 
                        id="ad_break_strategy" name="ad_break_strategy">
                    <option value="static" {{ old('ad_break_strategy', $channel->ad_break_strategy ?? 'static') === 'static' ? 'selected' : '' }}>Static (Based on Interval)</option>
                    <option value="scte35" {{ old('ad_break_strategy', $channel->ad_break_strategy) === 'scte35' ? 'selected' : '' }}>SCTE-35 (From Manifest Tags)</option>
                    <option value="hybrid" {{ old('ad_break_strategy', $channel->ad_break_strategy) === 'hybrid' ? 'selected' : '' }}>Hybrid (SCTE-35 + Static Fallback)</option>
                </select>
                <small class="form-text text-muted">How ad breaks are detected</small>
                @error('ad_break_strategy')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="ad_break_interval_seconds">Ad Break Interval (seconds)</label>
                <input type="number" class="form-control @error('ad_break_interval_seconds') is-invalid @enderror" 
                       id="ad_break_interval_seconds" name="ad_break_interval_seconds" 
                       value="{{ old('ad_break_interval_seconds', $channel->ad_break_interval_seconds ?? 360) }}" 
                       min="0" step="60"
                       placeholder="360 = 6 minutes">
                <small class="form-text text-muted">
                    How often ad breaks appear (in seconds). 
                    <strong>Examples:</strong> 180 = 3 min, 360 = 6 min, 600 = 10 min, 900 = 15 min. 
                    Set to 0 to disable automatic mid-roll ads.
                </small>
                @error('ad_break_interval_seconds')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input @error('enable_pre_roll') is-invalid @enderror" 
                           id="enable_pre_roll" name="enable_pre_roll" value="1"
                           {{ (old('enable_pre_roll', $channel->enable_pre_roll) == true || old('enable_pre_roll', $channel->enable_pre_roll) == '1' || old('enable_pre_roll', $channel->enable_pre_roll) == 1) ? 'checked' : '' }}>
                    <label class="form-check-label" for="enable_pre_roll">
                        Enable Pre-Roll Ad (Iklan di Awal)
                    </label>
                </div>
                @error('enable_pre_roll')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
                <small class="form-text text-muted">
                    Jika diaktifkan, iklan akan muncul di awal stream sebelum konten dimulai.
                </small>
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select class="form-control @error('status') is-invalid @enderror" 
                        id="status" name="status" required>
                    <option value="active" {{ old('status', $channel->status) === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $channel->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                @error('status')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Update Channel</button>
            <a href="{{ route('channels.index') }}" class="btn btn-default">Cancel</a>
        </div>
    </form>
</div>
@endsection

