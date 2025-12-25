@extends('layouts.app')

@section('page_title', 'Campaigns')

@section('page_content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Campaigns List</h3>
        <div class="card-tools">
            <a href="{{ route('campaigns.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Add New Campaign
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
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Budget</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($campaigns as $campaign)
                <tr>
                    <td>{{ $campaign->id }}</td>
                    <td>{{ $campaign->name }}</td>
                    <td>{{ $campaign->tenant->name ?? 'N/A' }}</td>
                    <td>{{ $campaign->start_date->format('Y-m-d') }}</td>
                    <td>{{ $campaign->end_date->format('Y-m-d') }}</td>
                    <td>{{ $campaign->budget ? '$' . number_format($campaign->budget, 2) : 'N/A' }}</td>
                    <td>
                        <span class="badge badge-{{ $campaign->status === 'active' ? 'success' : 'danger' }}">
                            {{ ucfirst($campaign->status) }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('campaigns.show', $campaign) }}" class="btn btn-sm btn-info">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('campaigns.edit', $campaign) }}" class="btn btn-sm btn-warning">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('campaigns.destroy', $campaign) }}" method="POST" class="d-inline">
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
                    <td colspan="8" class="text-center">No campaigns found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $campaigns->links() }}
    </div>
</div>
@endsection

