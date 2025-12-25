@extends('layouts.app')

@section('page_title', 'API Keys')

@section('page_content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">API Keys Management</h3>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tenant Name</th>
                    <th>API Key</th>
                    <th>Channels</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tenants as $tenant)
                <tr>
                    <td>{{ $tenant->id }}</td>
                    <td>{{ $tenant->name }}</td>
                    <td>
                        <code>{{ $tenant->api_key ?? 'Not set' }}</code>
                    </td>
                    <td>{{ $tenant->channels_count ?? 0 }}</td>
                    <td>
                        <span class="badge badge-{{ $tenant->status === 'active' ? 'success' : 'danger' }}">
                            {{ ucfirst($tenant->status) }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('api-keys.show', $tenant) }}" class="btn btn-sm btn-info">
                            <i class="fas fa-eye"></i> View
                        </a>
                        <form action="{{ route('api-keys.regenerate', $tenant) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Are you sure? This will invalidate the current API key.')">
                                <i class="fas fa-sync"></i> Regenerate
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center">No tenants found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $tenants->links() }}
    </div>
</div>
@endsection

