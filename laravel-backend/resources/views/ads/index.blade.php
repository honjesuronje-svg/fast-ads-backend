@extends('layouts.app')

@section('page_title', 'Ads')

@section('page_content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Ads List</h3>
        <div class="card-tools">
            <a href="{{ route('ads.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Add New Ad
            </a>
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Tenant</th>
                    <th>Campaign</th>
                    <th>Duration</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ads as $ad)
                <tr>
                    <td>{{ $ad->id }}</td>
                    <td>{{ $ad->name }}</td>
                    <td>{{ $ad->tenant->name ?? 'N/A' }}</td>
                    <td>{{ $ad->campaign->name ?? 'N/A' }}</td>
                    <td>{{ $ad->duration_seconds }}s</td>
                    <td>{{ ucfirst($ad->ad_type) }}</td>
                    <td>
                        <span class="badge badge-{{ $ad->status === 'active' ? 'success' : 'danger' }}">
                            {{ ucfirst($ad->status) }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('ads.show', $ad) }}" class="btn btn-sm btn-info">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('ads.edit', $ad) }}" class="btn btn-sm btn-warning">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('ads.destroy', $ad) }}" method="POST" class="d-inline">
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
                    <td colspan="8" class="text-center">No ads found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $ads->links() }}
    </div>
</div>
@endsection

