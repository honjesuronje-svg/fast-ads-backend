@extends('layouts.app')

@section('page_title', 'Reports & Analytics (WIB)')

@push('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    .metric-card {
        border-left: 4px solid #007bff;
    }
    .metric-card.success {
        border-left-color: #28a745;
    }
    .metric-card.warning {
        border-left-color: #ffc107;
    }
    .metric-card.danger {
        border-left-color: #dc3545;
    }
    .metric-card.info {
        border-left-color: #17a2b8;
    }
</style>
@endpush

@section('page_content')
<div class="row">
    <!-- Filters Card -->
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Report Filters</h3>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('reports.index') }}" id="reportForm">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="tenant_id">Tenant *</label>
                                <select name="tenant_id" id="tenant_id" class="form-control" required>
                                    <option value="">Select Tenant</option>
                                    @foreach($tenants as $tenant)
                                        <option value="{{ $tenant->id }}" {{ $filters['tenant_id'] == $tenant->id ? 'selected' : '' }}>
                                            {{ $tenant->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="ad_id">Ad</label>
                                <select name="ad_id" id="ad_id" class="form-control">
                                    <option value="">All Ads</option>
                                    @foreach($ads as $ad)
                                        <option value="{{ $ad->id }}" {{ $filters['ad_id'] == $ad->id ? 'selected' : '' }}>
                                            {{ $ad->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="campaign_id">Campaign</label>
                                <select name="campaign_id" id="campaign_id" class="form-control">
                                    <option value="">All Campaigns</option>
                                    @foreach($campaigns as $campaign)
                                        <option value="{{ $campaign->id }}" {{ $filters['campaign_id'] == $campaign->id ? 'selected' : '' }}>
                                            {{ $campaign->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="channel_id">Channel</label>
                                <select name="channel_id" id="channel_id" class="form-control">
                                    <option value="">All Channels</option>
                                    @foreach($channels as $channel)
                                        <option value="{{ $channel->id }}" {{ $filters['channel_id'] == $channel->id ? 'selected' : '' }}>
                                            {{ $channel->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="start_date">Start Date (WIB)</label>
                                <input type="text" name="start_date" id="start_date" class="form-control" value="{{ $filters['start_date'] }}" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="end_date">End Date (WIB)</label>
                                <input type="text" name="end_date" id="end_date" class="form-control" value="{{ $filters['end_date'] }}" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="granularity">Granularity</label>
                                <select name="granularity" id="granularity" class="form-control">
                                    <option value="hour" {{ $filters['granularity'] == 'hour' ? 'selected' : '' }}>Hourly</option>
                                    <option value="day" {{ $filters['granularity'] == 'day' ? 'selected' : '' }}>Daily</option>
                                    <option value="week" {{ $filters['granularity'] == 'week' ? 'selected' : '' }}>Weekly</option>
                                    <option value="month" {{ $filters['granularity'] == 'month' ? 'selected' : '' }}>Monthly</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Generate Report
                                    </button>
                                    @if($reportData)
                                        <a href="{{ route('reports.export', $filters) }}" class="btn btn-success" target="_blank">
                                            <i class="fas fa-download"></i> Export CSV
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@if($reportData)
<!-- Summary Metrics -->
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="card metric-card success">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="mb-0">{{ number_format($reportData['totals']['impressions']) }}</h5>
                        <small class="text-muted">Total Impressions</small>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-eye fa-2x text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="card metric-card info">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="mb-0">{{ number_format($reportData['totals']['starts']) }}</h5>
                        <small class="text-muted">Starts</small>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-play fa-2x text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="card metric-card warning">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="mb-0">{{ number_format($reportData['totals']['completions']) }}</h5>
                        <small class="text-muted">Completions</small>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-check-circle fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="card metric-card danger">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="mb-0">{{ number_format($reportData['totals']['completion_rate'], 2) }}%</h5>
                        <small class="text-muted">Completion Rate</small>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-percentage fa-2x text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-3 col-6">
        <div class="card metric-card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="mb-0">{{ number_format($reportData['totals']['clicks']) }}</h5>
                        <small class="text-muted">Clicks</small>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-mouse-pointer fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="card metric-card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="mb-0">{{ number_format($reportData['totals']['click_through_rate'], 2) }}%</h5>
                        <small class="text-muted">CTR</small>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-chart-line fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="card metric-card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="mb-0">{{ number_format($reportData['totals']['unique_viewers']) }}</h5>
                        <small class="text-muted">Unique Viewers</small>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-users fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="card metric-card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="mb-0">{{ gmdate('H:i:s', $reportData['totals']['avg_duration_watched']) }}</h5>
                        <small class="text-muted">Avg Duration</small>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-clock fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Impressions Over Time</h3>
            </div>
            <div class="card-body">
                <canvas id="impressionsChart" height="100"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Completion Rate Over Time</h3>
            </div>
            <div class="card-body">
                <canvas id="completionChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Data Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Detailed Report Data</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Impressions</th>
                            <th>Starts</th>
                            <th>Completions</th>
                            <th>Clicks</th>
                            <th>Completion Rate</th>
                            <th>CTR</th>
                            <th>Unique Viewers</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reportData['data'] as $row)
                        <tr>
                            <td>{{ $row['date'] }}</td>
                            <td>{{ number_format($row['impressions']) }}</td>
                            <td>{{ number_format($row['starts']) }}</td>
                            <td>{{ number_format($row['completions']) }}</td>
                            <td>{{ number_format($row['clicks']) }}</td>
                            <td>{{ number_format($row['completion_rate'], 2) }}%</td>
                            <td>{{ number_format($row['click_through_rate'], 2) }}%</td>
                            <td>{{ number_format($row['unique_viewers']) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center">No data available for the selected period</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@else
<div class="row">
    <div class="col-12">
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> 
            @if(!$filters['tenant_id'])
                Please select a tenant and date range to generate a report.
            @else
                No data found for the selected filters. Try adjusting the date range or filters.
                <br><small>Tip: Make sure tracking events are being recorded and reports are aggregated.</small>
            @endif
        </div>
    </div>
</div>
@endif
@endsection

@push('js')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
    // Initialize date pickers (only once)
    if (!window.datePickersInitialized) {
        flatpickr("#start_date", {
            dateFormat: "Y-m-d",
            maxDate: "today"
        });
        flatpickr("#end_date", {
            dateFormat: "Y-m-d",
            maxDate: "today"
        });
        window.datePickersInitialized = true;
    }

    @if($reportData && count($reportData['data']) > 0)
    // Initialize charts (wrap in IIFE to avoid variable conflicts)
    (function() {
        // Destroy existing charts if they exist
        try {
            if (window.impressionsChart && typeof window.impressionsChart.destroy === 'function') {
                window.impressionsChart.destroy();
            }
        } catch(e) {
            console.log('No existing impressions chart to destroy');
        }
        try {
            if (window.completionChart && typeof window.completionChart.destroy === 'function') {
                window.completionChart.destroy();
            }
        } catch(e) {
            console.log('No existing completion chart to destroy');
        }
        
        // Impressions Chart
        var impressionsCtxEl = document.getElementById('impressionsChart');
        if (impressionsCtxEl) {
            var impressionsCtx = impressionsCtxEl.getContext('2d');
            window.impressionsChart = new Chart(impressionsCtx, {
                type: 'line',
                data: {
                    labels: {!! json_encode(collect($reportData['data'])->pluck('date')->toArray()) !!},
                    datasets: [{
                        label: 'Impressions',
                        data: {!! json_encode(collect($reportData['data'])->pluck('impressions')->toArray()) !!},
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // Completion Rate Chart
        var completionCtxEl = document.getElementById('completionChart');
        if (completionCtxEl) {
            var completionCtx = completionCtxEl.getContext('2d');
            window.completionChart = new Chart(completionCtx, {
                type: 'bar',
                data: {
                    labels: {!! json_encode(collect($reportData['data'])->pluck('date')->toArray()) !!},
                    datasets: [{
                        label: 'Completion Rate (%)',
                        data: {!! json_encode(collect($reportData['data'])->pluck('completion_rate')->toArray()) !!},
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgb(255, 99, 132)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        }
    })();
    @endif
</script>
@endpush

