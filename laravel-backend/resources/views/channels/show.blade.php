@extends('layouts.app')

@section('page_title', 'Channel Details')

@section('page_content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Channel Details</h3>
        <div class="card-tools">
            <a href="{{ route('channels.edit', $channel) }}" class="btn btn-warning btn-sm">
                <i class="fas fa-edit"></i> Edit
            </a>
        </div>
    </div>
    <div class="card-body">
        <dl class="row">
            <dt class="col-sm-3">ID</dt>
            <dd class="col-sm-9">{{ $channel->id }}</dd>

            <dt class="col-sm-3">Name</dt>
            <dd class="col-sm-9">{{ $channel->name }}</dd>

            <dt class="col-sm-3">Slug</dt>
            <dd class="col-sm-9">{{ $channel->slug }}</dd>

            <dt class="col-sm-3">Tenant</dt>
            <dd class="col-sm-9">{{ $channel->tenant->name ?? 'N/A' }}</dd>

            <dt class="col-sm-3">HLS Manifest URL</dt>
            <dd class="col-sm-9">{{ $channel->hls_manifest_url ?? 'N/A' }}</dd>

            <dt class="col-sm-3">Status</dt>
            <dd class="col-sm-9">
                <span class="badge badge-{{ $channel->status === 'active' ? 'success' : 'danger' }}">
                    {{ ucfirst($channel->status) }}
                </span>
            </dd>

            <dt class="col-sm-3">Created At</dt>
            <dd class="col-sm-9">{{ $channel->created_at->format('Y-m-d H:i:s') }}</dd>
        </dl>
    </div>
</div>

<!-- SSAI Endpoint Information -->
<div class="card card-info">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-link"></i> SSAI Endpoint URL</h3>
    </div>
    <div class="card-body">
        <p class="text-muted">Use this URL to register in your OTT platform. This is the SSAI endpoint that will serve ads with your content.</p>
        
        <div class="form-group">
            <label for="ssai_url">SSAI Manifest URL</label>
            <div class="input-group">
                <input type="text" 
                       class="form-control" 
                       id="ssai_url" 
                       value="https://doubleclick.wkkworld.com/fast/{{ $channel->tenant->slug }}/{{ $channel->slug }}.m3u8" 
                       readonly>
                <div class="input-group-append">
                    <button class="btn btn-primary" type="button" onclick="copySSAIUrl()">
                        <i class="fas fa-copy"></i> Copy
                    </button>
                </div>
            </div>
            <small class="form-text text-muted">
                Format: <code>https://doubleclick.wkkworld.com/fast/{tenant_slug}/{channel_slug}.m3u8</code>
            </small>
        </div>

        <div class="alert alert-info">
            <h5><i class="icon fas fa-info"></i> How to Use:</h5>
            <ol>
                <li>Copy the SSAI URL above</li>
                <li>Register this URL in your OTT platform as the HLS manifest URL</li>
                <li>When players request this URL, the Golang SSAI service will:
                    <ul>
                        <li>Fetch original content manifest from: <strong>{{ $channel->hls_manifest_url ?? 'Not configured' }}</strong></li>
                        <li>Detect ad breaks based on channel configuration</li>
                        <li>Call Laravel API for ad decisions</li>
                        <li>Stitch ads into the manifest</li>
                        <li>Return stitched manifest to player</li>
                    </ul>
                </li>
            </ol>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card card-secondary">
                    <div class="card-header">
                        <h3 class="card-title">Channel Configuration</h3>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-6">Ad Break Strategy:</dt>
                            <dd class="col-sm-6">
                                <span class="badge badge-info">{{ ucfirst($channel->ad_break_strategy ?? 'static') }}</span>
                            </dd>
                            
                            <dt class="col-sm-6">Ad Break Interval:</dt>
                            <dd class="col-sm-6">
                                @if($channel->ad_break_interval_seconds)
                                    {{ $channel->ad_break_interval_seconds }} seconds 
                                    ({{ round($channel->ad_break_interval_seconds / 60, 1) }} minutes)
                                @else
                                    <span class="text-muted">Not set</span>
                                @endif
                            </dd>
                            
                            <dt class="col-sm-6">Original HLS URL:</dt>
                            <dd class="col-sm-6">
                                @if($channel->hls_manifest_url)
                                    <a href="{{ $channel->hls_manifest_url }}" target="_blank" class="text-break">
                                        {{ Str::limit($channel->hls_manifest_url, 50) }}
                                    </a>
                                @else
                                    <span class="text-danger">Not configured</span>
                                @endif
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card card-secondary">
                    <div class="card-header">
                        <h3 class="card-title">API Information</h3>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-6">Tenant Slug:</dt>
                            <dd class="col-sm-6"><code>{{ $channel->tenant->slug }}</code></dd>
                            
                            <dt class="col-sm-6">Channel Slug:</dt>
                            <dd class="col-sm-6"><code>{{ $channel->slug }}</code></dd>
                            
                            <dt class="col-sm-6">API Base URL:</dt>
                            <dd class="col-sm-6"><code>https://doubleclick.wkkworld.com</code></dd>
                            
                            <dt class="col-sm-6">SSAI Endpoint:</dt>
                            <dd class="col-sm-6"><code>/fast/{tenant}/{channel}.m3u8</code></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copySSAIUrl() {
    const urlInput = document.getElementById('ssai_url');
    urlInput.select();
    urlInput.setSelectionRange(0, 99999); // For mobile devices
    
    try {
        document.execCommand('copy');
        
        // Show success message
        const btn = event.target.closest('button');
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-success');
        
        setTimeout(() => {
            btn.innerHTML = originalHTML;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-primary');
        }, 2000);
    } catch (err) {
        alert('Failed to copy URL. Please copy manually.');
    }
}
</script>

<div class="card-footer">
    <a href="{{ route('channels.index') }}" class="btn btn-default">Back to List</a>
    <a href="{{ route('channels.edit', $channel) }}" class="btn btn-warning">
        <i class="fas fa-edit"></i> Edit Channel
    </a>
</div>
@endsection

