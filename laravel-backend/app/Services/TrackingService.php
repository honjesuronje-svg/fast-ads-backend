<?php

namespace App\Services;

use App\Models\TrackingEvent;
use App\Models\Ad;
use App\Models\AdCampaign;
use App\Services\FrequencyCapService;
use Illuminate\Support\Facades\DB;

class TrackingService
{
    protected FrequencyCapService $frequencyCapService;

    public function __construct(FrequencyCapService $frequencyCapService)
    {
        $this->frequencyCapService = $frequencyCapService;
    }

    /**
     * Record a tracking event
     */
    public function recordEvent(array $eventData): TrackingEvent
    {
        $event = TrackingEvent::create([
            'tenant_id' => $eventData['tenant_id'],
            'channel_id' => $eventData['channel_id'] ?? null,
            'ad_id' => $eventData['ad_id'],
            'event_type' => $eventData['event_type'],
            'session_id' => $eventData['session_id'] ?? null,
            'device_type' => $eventData['device_type'] ?? null,
            'geo_country' => $eventData['geo_country'] ?? null,
            'ip_address' => $eventData['ip_address'] ?? null,
            'user_agent' => $eventData['user_agent'] ?? null,
            'timestamp' => $eventData['timestamp'] ?? now(),
            'metadata' => $eventData['metadata'] ?? null,
        ]);

        // Record frequency cap impression if this is an impression event
        if ($eventData['event_type'] === 'impression' && isset($eventData['ad_id'])) {
            $ad = Ad::find($eventData['ad_id']);
            if ($ad && isset($eventData['session_id'])) {
                $this->frequencyCapService->recordImpression(
                    $ad,
                    $eventData['session_id'],
                    'session'
                );

                // Also record campaign-level impression
                if ($ad->campaign) {
                    $this->frequencyCapService->recordCampaignImpression(
                        $ad->campaign,
                        $eventData['session_id'],
                        'session'
                    );
                }
            }
        }

        return $event;
    }

    /**
     * Get impression reports
     */
    public function getImpressionReport(
        int $tenantId,
        string $startDate,
        string $endDate,
        ?int $channelId = null,
        ?int $adId = null,
        ?string $groupBy = 'day',
    ): array {
        $query = TrackingEvent::where('tenant_id', $tenantId)
            ->where('event_type', 'impression')
            ->whereBetween('timestamp', [$startDate, $endDate]);

        if ($channelId) {
            $query->where('channel_id', $channelId);
        }

        if ($adId) {
            $query->where('ad_id', $adId);
        }

        $totalImpressions = $query->count();
        $totalCompletes = TrackingEvent::where('tenant_id', $tenantId)
            ->where('event_type', 'complete')
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->when($channelId, fn($q) => $q->where('channel_id', $channelId))
            ->when($adId, fn($q) => $q->where('ad_id', $adId))
            ->count();

        $breakdown = match ($groupBy) {
            'day' => $this->getDailyBreakdown($query, $startDate, $endDate),
            'hour' => $this->getHourlyBreakdown($query, $startDate, $endDate),
            'channel' => $this->getChannelBreakdown($query),
            'ad' => $this->getAdBreakdown($query),
            default => [],
        };

        return [
            'summary' => [
                'total_impressions' => $totalImpressions,
                'total_completes' => $totalCompletes,
                'completion_rate' => $totalImpressions > 0 ? $totalCompletes / $totalImpressions : 0,
            ],
            'breakdown' => $breakdown,
        ];
    }

    protected function getDailyBreakdown($query, string $startDate, string $endDate): array
    {
        return $query->select(
            DB::raw('DATE(timestamp) as date'),
            DB::raw('COUNT(*) as impressions')
        )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($row) {
                return [
                    'date' => $row->date,
                    'impressions' => $row->impressions,
                ];
            })
            ->toArray();
    }

    protected function getHourlyBreakdown($query, string $startDate, string $endDate): array
    {
        return $query->select(
            DB::raw('DATE(timestamp) as date'),
            DB::raw('HOUR(timestamp) as hour'),
            DB::raw('COUNT(*) as impressions')
        )
            ->groupBy('date', 'hour')
            ->orderBy('date')
            ->orderBy('hour')
            ->get()
            ->toArray();
    }

    protected function getChannelBreakdown($query): array
    {
        return $query->select(
            'channel_id',
            DB::raw('COUNT(*) as impressions')
        )
            ->groupBy('channel_id')
            ->get()
            ->toArray();
    }

    protected function getAdBreakdown($query): array
    {
        return $query->select(
            'ad_id',
            DB::raw('COUNT(*) as impressions')
        )
            ->groupBy('ad_id')
            ->get()
            ->toArray();
    }
}

