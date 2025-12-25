@extends('layouts.app')

@section('page_title', 'API Key Details')

@section('page_content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">API Key Details - {{ $tenant->name }}</h3>
    </div>
    <div class="card-body">
        <dl class="row">
            <dt class="col-sm-3">Tenant ID</dt>
            <dd class="col-sm-9">{{ $tenant->id }}</dd>

            <dt class="col-sm-3">Tenant Name</dt>
            <dd class="col-sm-9">{{ $tenant->name }}</dd>

            <dt class="col-sm-3">API Key</dt>
            <dd class="col-sm-9">
                <code style="font-size: 14px;">{{ $tenant->api_key ?? 'Not set' }}</code>
                <button class="btn btn-sm btn-secondary ml-2" onclick="copyToClipboard('{{ $tenant->api_key }}')">
                    <i class="fas fa-copy"></i> Copy
                </button>
            </dd>

            <dt class="col-sm-3">Status</dt>
            <dd class="col-sm-9">
                <span class="badge badge-{{ $tenant->status === 'active' ? 'success' : 'danger' }}">
                    {{ ucfirst($tenant->status) }}
                </span>
            </dd>
        </dl>

        <div class="alert alert-warning">
            <strong>Warning:</strong> Regenerating the API key will invalidate the current key. Make sure to update all integrations using this key.
        </div>
    </div>
    <div class="card-footer">
        <form action="{{ route('api-keys.regenerate', $tenant) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-warning" onclick="return confirm('Are you sure? This will invalidate the current API key.')">
                <i class="fas fa-sync"></i> Regenerate API Key
            </button>
        </form>
        <a href="{{ route('api-keys.index') }}" class="btn btn-default">Back to List</a>
    </div>
</div>

@push('js')
<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('API Key copied to clipboard!');
    }, function(err) {
        console.error('Failed to copy: ', err);
    });
}
</script>
@endpush
@endsection

