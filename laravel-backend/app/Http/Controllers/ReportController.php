<?php

namespace App\Http\Controllers;

use App\Services\ReportingService;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    protected ReportingService $reportingService;

    public function __construct(ReportingService $reportingService)
    {
        $this->reportingService = $reportingService;
    }

    /**
     * Get ad report
     */
    public function getReport(Request $request, Tenant $tenant): JsonResponse
    {
        $request->validate([
            'ad_id' => 'nullable|integer',
            'campaign_id' => 'nullable|integer',
            'channel_id' => 'nullable|integer',
            'variant_id' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'granularity' => 'nullable|in:hour,day,week,month',
        ]);

        $report = $this->reportingService->generateReport(
            tenantId: $tenant->id,
            adId: $request->input('ad_id'),
            campaignId: $request->input('campaign_id'),
            channelId: $request->input('channel_id'),
            variantId: $request->input('variant_id'),
            startDate: $request->input('start_date'),
            endDate: $request->input('end_date'),
            granularity: $request->input('granularity', 'hour')
        );

        return response()->json($report);
    }

    /**
     * Export report to CSV
     */
    public function exportReport(Request $request, Tenant $tenant)
    {
        $request->validate([
            'ad_id' => 'nullable|integer',
            'campaign_id' => 'nullable|integer',
            'channel_id' => 'nullable|integer',
            'variant_id' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'granularity' => 'nullable|in:hour,day,week,month',
        ]);

        $report = $this->reportingService->generateReport(
            tenantId: $tenant->id,
            adId: $request->input('ad_id'),
            campaignId: $request->input('campaign_id'),
            channelId: $request->input('channel_id'),
            variantId: $request->input('variant_id'),
            startDate: $request->input('start_date'),
            endDate: $request->input('end_date'),
            granularity: $request->input('granularity', 'day')
        );

        $csvPath = $this->reportingService->exportToCsv($report);

        return response()->download($csvPath)->deleteFileAfterSend();
    }
}

