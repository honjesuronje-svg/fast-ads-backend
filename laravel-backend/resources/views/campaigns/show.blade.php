@extends('layouts.app')

@section('page_title', 'Campaign Details')

@section('page_content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Campaign Details</h3>
        <div class="card-tools">
            <a href="{{ route('campaigns.edit', $campaign) }}" class="btn btn-warning btn-sm">
                <i class="fas fa-edit"></i> Edit
            </a>
        </div>
    </div>
    <div class="card-body">
        <dl class="row">
            <dt class="col-sm-3">ID</dt>
            <dd class="col-sm-9">{{ $campaign->id }}</dd>

            <dt class="col-sm-3">Name</dt>
            <dd class="col-sm-9">{{ $campaign->name }}</dd>

            <dt class="col-sm-3">Tenant</dt>
            <dd class="col-sm-9">{{ $campaign->tenant->name ?? 'N/A' }}</dd>

            <dt class="col-sm-3">Description</dt>
            <dd class="col-sm-9">{{ $campaign->description ?? 'N/A' }}</dd>

            <dt class="col-sm-3">Start Date</dt>
            <dd class="col-sm-9">{{ $campaign->start_date->format('Y-m-d') }}</dd>

            <dt class="col-sm-3">End Date</dt>
            <dd class="col-sm-9">{{ $campaign->end_date->format('Y-m-d') }}</dd>

            <dt class="col-sm-3">Budget</dt>
            <dd class="col-sm-9">{{ $campaign->budget ? '$' . number_format($campaign->budget, 2) : 'N/A' }}</dd>

            <dt class="col-sm-3">Status</dt>
            <dd class="col-sm-9">
                <span class="badge badge-{{ $campaign->status === 'active' ? 'success' : 'danger' }}">
                    {{ ucfirst($campaign->status) }}
                </span>
            </dd>

            <dt class="col-sm-3">Created At</dt>
            <dd class="col-sm-9">{{ $campaign->created_at->format('Y-m-d H:i:s') }}</dd>
        </dl>
    </div>
    <div class="card-footer">
        <a href="{{ route('campaigns.index') }}" class="btn btn-default">Back to List</a>
    </div>
</div>
@endsection

