<?php

namespace App\Http\Controllers;

use App\Services\ReportingService;
use App\Models\Tenant;
use App\Models\Ad;
use App\Models\AdCampaign;
use App\Models\Channel;
use Illuminate\Http\Request;

class ReportDashboardController extends Controller
{
    protected ReportingService $reportingService;

    public function __construct(ReportingService $reportingService)
    {
        $this->reportingService = $reportingService;
    }

    /**
     * Display reports dashboard
     */
    public function index(Request $request)
    {
        $tenants = Tenant::where('status', 'active')->get();
        $ads = Ad::where('status', 'active')->get();
        $campaigns = AdCampaign::where('status', 'active')->get();
        $channels = Channel::where('status', 'active')->get();

        // Default filters (use Asia/Jakarta timezone)
        $filters = [
            'tenant_id' => $request->input('tenant_id'),
            'ad_id' => $request->input('ad_id'),
            'campaign_id' => $request->input('campaign_id'),
            'channel_id' => $request->input('channel_id'),
            'start_date' => $request->input('start_date', now('Asia/Jakarta')->subDays(7)->format('Y-m-d')),
            'end_date' => $request->input('end_date', now('Asia/Jakarta')->format('Y-m-d')),
            'granularity' => $request->input('granularity', 'day'),
        ];

        // Auto-select tenant if none selected
        // Prefer tenant with most recent reports, or first tenant
        if (!$filters['tenant_id'] && $tenants->count() > 0) {
            // Try to find tenant with most recent reports
            $tenantWithReports = \App\Models\AdReport::select('tenant_id')
                ->groupBy('tenant_id')
                ->orderByRaw('MAX(report_date) DESC')
                ->first();
            
            if ($tenantWithReports) {
                $filters['tenant_id'] = $tenantWithReports->tenant_id;
            } else {
                $filters['tenant_id'] = $tenants->first()->id;
            }
        }

        // Get report data if filters are set
        $reportData = null;
        if ($filters['tenant_id']) {
            $reportData = $this->reportingService->generateReport(
                tenantId: $filters['tenant_id'],
                adId: $filters['ad_id'],
                campaignId: $filters['campaign_id'],
                channelId: $filters['channel_id'],
                startDate: $filters['start_date'],
                endDate: $filters['end_date'],
                granularity: $filters['granularity']
            );
        }

        return view('reports.index', compact(
            'tenants',
            'ads',
            'campaigns',
            'channels',
            'filters',
            'reportData'
        ));
    }

    /**
     * Get report data as JSON (for AJAX)
     */
    public function getData(Request $request)
    {
        $request->validate([
            'tenant_id' => 'required|integer',
            'ad_id' => 'nullable|integer',
            'campaign_id' => 'nullable|integer',
            'channel_id' => 'nullable|integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'granularity' => 'required|in:hour,day,week,month',
        ]);

        $report = $this->reportingService->generateReport(
            tenantId: $request->tenant_id,
            adId: $request->ad_id,
            campaignId: $request->campaign_id,
            channelId: $request->channel_id,
            startDate: $request->start_date,
            endDate: $request->end_date,
            granularity: $request->granularity
        );

        return response()->json($report);
    }

    /**
     * Export report to CSV
     */
    public function export(Request $request)
    {
        $request->validate([
            'tenant_id' => 'required|integer',
            'ad_id' => 'nullable|integer',
            'campaign_id' => 'nullable|integer',
            'channel_id' => 'nullable|integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'granularity' => 'required|in:hour,day,week,month',
        ]);

        $report = $this->reportingService->generateReport(
            tenantId: $request->input('tenant_id'),
            adId: $request->input('ad_id'),
            campaignId: $request->input('campaign_id'),
            channelId: $request->input('channel_id'),
            startDate: $request->input('start_date'),
            endDate: $request->input('end_date'),
            granularity: $request->input('granularity')
        );

        $csvPath = $this->reportingService->exportToCsv($report);

        return response()->download($csvPath)->deleteFileAfterSend();
    }
}

