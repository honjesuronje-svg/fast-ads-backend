@extends('layouts.app')

@section('page_title', 'Tenants')

@section('page_content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Tenants List</h3>
        <div class="card-tools">
            <a href="{{ route('tenants.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Add New Tenant
            </a>
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tenants as $tenant)
                <tr>
                    <td>{{ $tenant->id }}</td>
                    <td>{{ $tenant->name }}</td>
                    <td>{{ $tenant->slug }}</td>
                    <td>
                        <span class="badge badge-{{ $tenant->status === 'active' ? 'success' : 'danger' }}">
                            {{ ucfirst($tenant->status) }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('tenants.show', $tenant) }}" class="btn btn-sm btn-info">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('tenants.edit', $tenant) }}" class="btn btn-sm btn-warning">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('tenants.destroy', $tenant) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center">No tenants found.</td>
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

