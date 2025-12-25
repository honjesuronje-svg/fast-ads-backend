@extends('layouts.app')

@section('page_title', 'Edit Ad')

@section('page_content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Create New Ad</h3>
    </div>
    <form action="{{ route('ads.update', $ad) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="card-body">
            <div class="form-group">
                <label for="tenant_id">Tenant</label>
                <select class="form-control @error('tenant_id') is-invalid @enderror" 
                        id="tenant_id" name="tenant_id" required>
                    <option value="">Select Tenant</option>
                    @foreach($tenants as $tenant)
                        <option value="{{ $tenant->id }}" {{ old('tenant_id', $ad->tenant_id) == $tenant->id ? 'selected' : '' }}>
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
                <label for="campaign_id">Campaign (Optional)</label>
                <select class="form-control @error('campaign_id') is-invalid @enderror" 
                        id="campaign_id" name="campaign_id">
                    <option value="">None</option>
                    @foreach($campaigns as $campaign)
                        <option value="{{ $campaign->id }}" {{ old('campaign_id', $ad->campaign_id) == $campaign->id ? 'selected' : '' }}>
                            {{ $campaign->name }}
                        </option>
                    @endforeach
                </select>
                @error('campaign_id')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                       id="name" name="name" value="{{ old('name', $ad->name) }}" required>
                @error('name')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="ad_source">Ad Source</label>
                <select class="form-control @error('ad_source') is-invalid @enderror" 
                        id="ad_source" name="ad_source" required onchange="toggleAdSourceEdit()">
                    <option value="vast_url" {{ old('ad_source', $ad->ad_source) === 'vast_url' ? 'selected' : '' }}>VAST URL (External Ad Server)</option>
                    <option value="uploaded_video" {{ old('ad_source') === 'uploaded_video' ? 'selected' : '' }}>Upload Video (Auto-generate VAST)</option>
                </select>
                @error('ad_source')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group" id="vast_url_group">
                <label for="vast_url">VAST URL</label>
                <input type="url" class="form-control @error('vast_url') is-invalid @enderror" 
                       id="vast_url" name="vast_url" value="{{ old('vast_url', $ad->vast_url) }}" 
                       placeholder="https://adserver.com/vast2.xml or https://googleads.g.doubleclick.net/...">
                <small class="form-text text-muted">Enter VAST URL from external ad server (e.g., Google Ads, AdX, FreeWheel)</small>
                @error('vast_url')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group" id="video_file_group" style="display: none;">
                <label for="video_file">Video File</label>
                <input type="file" class="form-control @error('video_file') is-invalid @enderror" 
                       id="video_file" name="video_file" accept="video/mp4,video/webm,video/avi,video/quicktime">
                <small class="form-text text-muted">Upload video file (MP4, WebM, AVI, MOV). Max 500MB. VAST XML will be auto-generated.</small>
                @error('video_file')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="duration_seconds">Duration (seconds)</label>
                <input type="number" class="form-control @error('duration_seconds') is-invalid @enderror" 
                       id="duration_seconds" name="duration_seconds" value="{{ old('duration_seconds', $ad->duration_seconds) }}" min="1" required>
                @error('duration_seconds')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="ad_type">Ad Type</label>
                <select class="form-control @error('ad_type') is-invalid @enderror" 
                        id="ad_type" name="ad_type" required>
                    <option value="linear" {{ old('ad_type', $ad->ad_type) === 'linear' ? 'selected' : '' }}>Linear</option>
                    <option value="non-linear" {{ old('ad_type', $ad->ad_type) === 'non-linear' ? 'selected' : '' }}>Non-Linear</option>
                    <option value="companion" {{ old('ad_type', $ad->ad_type) === 'companion' ? 'selected' : '' }}>Companion</option>
                </select>
                @error('ad_type')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="click_through_url">Click Through URL (Optional)</label>
                <input type="url" class="form-control @error('click_through_url') is-invalid @enderror" 
                       id="click_through_url" name="click_through_url" value="{{ old('click_through_url', $ad->click_through_url) }}">
                @error('click_through_url')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select class="form-control @error('status') is-invalid @enderror" 
                        id="status" name="status" required>
                    <option value="active" {{ old('status', $ad->status) === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $ad->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                @error('status')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Edit Ad</button>
            <a href="{{ route('ads.index') }}" class="btn btn-default">Cancel</a>
        </div>
    </form>
</div>

<script>
function toggleAdSourceEdit() {
    const adSource = document.getElementById('ad_source').value;
    const vastUrlGroup = document.getElementById('vast_url_group');
    const videoFileGroup = document.getElementById('video_file_group');
    const vastUrlInput = document.getElementById('vast_url');
    const videoFileInput = document.getElementById('video_file');
    
    if (adSource === 'vast_url') {
        vastUrlGroup.style.display = 'block';
        videoFileGroup.style.display = 'none';
        vastUrlInput.setAttribute('required', 'required');
        videoFileInput.removeAttribute('required');
    } else {
        vastUrlGroup.style.display = 'none';
        videoFileGroup.style.display = 'block';
        vastUrlInput.removeAttribute('required');
        videoFileInput.setAttribute('required', 'required');
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleAdSourceEdit();
});
</script>
@endsection

