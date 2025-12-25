<?php

namespace App\Services;

use App\Models\AdReport;
use App\Models\TrackingEvent;
use App\Models\Ad;
use App\Models\AdCampaign;
use App\Models\Channel;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportingService
{
    /**
     * Generate report for a specific date range
     */
    public function generateReport(
        int $tenantId,
        ?int $adId = null,
        ?int $campaignId = null,
        ?int $channelId = null,
        ?int $variantId = null,
        string $startDate = null,
        string $endDate = null,
        string $granularity = 'hour'
    ): array {
        // Parse dates in Asia/Jakarta timezone
        $startDate = $startDate 
            ? Carbon::parse($startDate)->setTimezone('Asia/Jakarta')->startOfDay()
            : Carbon::today('Asia/Jakarta');
        $endDate = $endDate 
            ? Carbon::parse($endDate)->setTimezone('Asia/Jakarta')->endOfDay()
            : Carbon::today('Asia/Jakarta')->endOfDay();

        // Use date comparison instead of whereBetween for better compatibility
        $query = AdReport::where('tenant_id', $tenantId)
            ->whereDate('report_date', '>=', $startDate->toDateString())
            ->whereDate('report_date', '<=', $endDate->toDateString())
            ->where('time_granularity', $granularity);

        if ($adId) {
            $query->where('ad_id', $adId);
        }

        if ($campaignId) {
            $query->where('campaign_id', $campaignId);
        }

        if ($channelId) {
            $query->where('channel_id', $channelId);
        }

        if ($variantId) {
            $query->where('variant_id', $variantId);
        }

        $reports = $query->get();

        // Aggregate totals
        $totals = [
            'impressions' => $reports->sum('impressions'),
            'starts' => $reports->sum('starts'),
            'completions' => $reports->sum('completions'),
            'clicks' => $reports->sum('clicks'),
            'revenue' => $reports->sum('revenue'),
            'unique_viewers' => $reports->sum('unique_viewers'),
            'total_duration_watched' => $reports->sum('total_duration_watched'),
        ];

        // Calculate rates
        $totals['completion_rate'] = $totals['impressions'] > 0 
            ? ($totals['completions'] / $totals['impressions']) * 100 
            : 0;
        
        $totals['click_through_rate'] = $totals['impressions'] > 0 
            ? ($totals['clicks'] / $totals['impressions']) * 100 
            : 0;

        $totals['avg_duration_watched'] = $totals['starts'] > 0 
            ? $totals['total_duration_watched'] / $totals['starts'] 
            : 0;

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
                'granularity' => $granularity,
            ],
            'totals' => $totals,
            'data' => $reports->map(function ($report) {
                return [
                    'date' => $report->report_date->toDateString(),
                    'impressions' => $report->impressions,
                    'starts' => $report->starts,
                    'completions' => $report->completions,
                    'clicks' => $report->clicks,
                    'completion_rate' => $report->completion_rate,
                    'click_through_rate' => $report->click_through_rate,
                    'revenue' => $report->revenue,
                    'unique_viewers' => $report->unique_viewers,
                ];
            })->toArray(), // Convert to array for view
        ];
    }

    /**
     * Aggregate tracking events into reports
     */
    public function aggregateTrackingEvents(string $date = null): void
    {
        $date = $date ? Carbon::parse($date)->setTimezone('Asia/Jakarta') : Carbon::yesterday('Asia/Jakarta');
        
        // Get all tracking events for the date (handle timezone)
        $startOfDay = $date->copy()->startOfDay()->setTimezone('UTC');
        $endOfDay = $date->copy()->endOfDay()->setTimezone('UTC');
        
        $events = TrackingEvent::whereBetween('timestamp', [$startOfDay, $endOfDay])
            ->get()
            ->groupBy(function ($event) {
                return sprintf(
                    '%d:%d:%d:%d:%d',
                    $event->tenant_id,
                    $event->ad_id ?? 0,
                    $event->campaign_id ?? 0,
                    $event->channel_id ?? 0,
                    $event->variant_id ?? 0
                );
            });

        foreach ($events as $key => $group) {
            $firstEvent = $group->first();
            
            // Calculate metrics
            $impressions = $group->where('event_type', 'impression')->count();
            $starts = $group->where('event_type', 'start')->count();
            $completions = $group->where('event_type', 'complete')->count();
            $clicks = $group->where('event_type', 'click')->count();
            $uniqueViewers = $group->pluck('session_id')->unique()->count();
            
            // Calculate duration watched (simplified - would need more tracking)
            $totalDuration = $group->where('event_type', 'complete')
                ->sum(function ($event) {
                    return $event->metadata['duration'] ?? 0;
                });

            // Find existing report or create new (use local date)
            $report = AdReport::firstOrNew([
                'tenant_id' => $firstEvent->tenant_id,
                'ad_id' => $firstEvent->ad_id,
                'campaign_id' => $firstEvent->campaign_id,
                'channel_id' => $firstEvent->channel_id,
                'variant_id' => $firstEvent->variant_id ?? null,
                'report_date' => $date->setTimezone('Asia/Jakarta')->toDateString(),
                'time_granularity' => 'day',
            ]);

            // Update or set values
            if ($report->exists) {
                $report->impressions += $impressions;
                $report->starts += $starts;
                $report->completions += $completions;
                $report->clicks += $clicks;
                $report->total_duration_watched += $totalDuration;
            } else {
                $report->impressions = $impressions;
                $report->starts = $starts;
                $report->completions = $completions;
                $report->clicks = $clicks;
                $report->total_duration_watched = $totalDuration;
            }

            // Recalculate rates
            $report->unique_viewers = $uniqueViewers;
            $report->completion_rate = $report->impressions > 0 
                ? ($report->completions / $report->impressions) * 100 
                : 0;
            $report->click_through_rate = $report->impressions > 0 
                ? ($report->clicks / $report->impressions) * 100 
                : 0;
            $report->avg_duration_watched = $report->starts > 0 
                ? ($report->total_duration_watched / $report->starts) 
                : 0;

            $report->save();
        }
    }

    /**
     * Export report to CSV
     */
    public function exportToCsv(array $reportData, string $filename = null): string
    {
        $filename = $filename ?? 'ad_report_' . date('Y-m-d') . '.csv';
        $path = storage_path('app/reports/' . $filename);

        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $file = fopen($path, 'w');

        // Write header
        fputcsv($file, [
            'Date',
            'Impressions',
            'Starts',
            'Completions',
            'Clicks',
            'Completion Rate (%)',
            'Click Through Rate (%)',
            'Revenue',
            'Unique Viewers',
        ]);

        // Write data
        foreach ($reportData['data'] as $row) {
            fputcsv($file, [
                $row['date'],
                $row['impressions'],
                $row['starts'],
                $row['completions'],
                $row['clicks'],
                number_format($row['completion_rate'], 2),
                number_format($row['click_through_rate'], 2),
                number_format($row['revenue'], 2),
                $row['unique_viewers'],
            ]);
        }

        fclose($file);

        return $path;
    }
}

