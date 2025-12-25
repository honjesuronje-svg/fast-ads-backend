@extends('layouts.app')

@section('page_title', 'Tenant Details')

@section('page_content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Tenant Details</h3>
        <div class="card-tools">
            <a href="{{ route('tenants.edit', $tenant) }}" class="btn btn-warning btn-sm">
                <i class="fas fa-edit"></i> Edit
            </a>
        </div>
    </div>
    <div class="card-body">
        <dl class="row">
            <dt class="col-sm-3">ID</dt>
            <dd class="col-sm-9">{{ $tenant->id }}</dd>

            <dt class="col-sm-3">Name</dt>
            <dd class="col-sm-9">{{ $tenant->name }}</dd>

            <dt class="col-sm-3">Slug</dt>
            <dd class="col-sm-9">{{ $tenant->slug }}</dd>

            <dt class="col-sm-3">Status</dt>
            <dd class="col-sm-9">
                <span class="badge badge-{{ $tenant->status === 'active' ? 'success' : 'danger' }}">
                    {{ ucfirst($tenant->status) }}
                </span>
            </dd>

            <dt class="col-sm-3">API Key</dt>
            <dd class="col-sm-9">
                <code>{{ $tenant->api_key ?? 'Not set' }}</code>
            </dd>

            <dt class="col-sm-3">Created At</dt>
            <dd class="col-sm-9">{{ $tenant->created_at->format('Y-m-d H:i:s') }}</dd>

            <dt class="col-sm-3">Updated At</dt>
            <dd class="col-sm-9">{{ $tenant->updated_at->format('Y-m-d H:i:s') }}</dd>
        </dl>
    </div>
    <div class="card-footer">
        <a href="{{ route('tenants.index') }}" class="btn btn-default">Back to List</a>
    </div>
</div>
@endsection

