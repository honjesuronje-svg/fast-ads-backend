<?php

namespace App\Services;

use App\Models\FrequencyCap;
use App\Models\Ad;
use App\Models\AdCampaign;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class FrequencyCapService
{
    /**
     * Check if ad can be shown based on frequency cap
     */
    public function canShowAd(
        Ad $ad,
        string $viewerIdentifier,
        string $identifierType = 'session',
        ?int $maxImpressions = null,
        ?string $timeWindow = null
    ): bool {
        // Get frequency cap settings from ad or campaign
        $maxImpressions = $maxImpressions ?? $ad->metadata['frequency_cap']['max_impressions'] ?? 3;
        $timeWindow = $timeWindow ?? $ad->metadata['frequency_cap']['time_window'] ?? 'day';

        // Check cache first
        $cacheKey = $this->getCacheKey($ad->id, $viewerIdentifier, $identifierType, $timeWindow);
        
        if (Cache::has($cacheKey)) {
            $count = Cache::get($cacheKey);
            return $count < $maxImpressions;
        }

        // Calculate time window
        $window = $this->calculateTimeWindow($timeWindow);
        
        // Check database
        $cap = FrequencyCap::where('ad_id', $ad->id)
            ->where('viewer_identifier', $viewerIdentifier)
            ->where('identifier_type', $identifierType)
            ->where('time_window', $timeWindow)
            ->where('window_start', '<=', now())
            ->where('window_end', '>=', now())
            ->first();

        if ($cap) {
            $canShow = $cap->impression_count < $maxImpressions;
            Cache::put($cacheKey, $cap->impression_count, $window['ttl']);
            return $canShow;
        }

        // No cap record = can show
        return true;
    }

    /**
     * Check campaign-level frequency cap
     */
    public function canShowCampaign(
        AdCampaign $campaign,
        string $viewerIdentifier,
        string $identifierType = 'session',
        ?int $maxImpressions = null,
        ?string $timeWindow = null
    ): bool {
        $maxImpressions = $maxImpressions ?? $campaign->metadata['frequency_cap']['max_impressions'] ?? 5;
        $timeWindow = $timeWindow ?? $campaign->metadata['frequency_cap']['time_window'] ?? 'day';

        $cacheKey = $this->getCampaignCacheKey($campaign->id, $viewerIdentifier, $identifierType, $timeWindow);
        
        if (Cache::has($cacheKey)) {
            $count = Cache::get($cacheKey);
            return $count < $maxImpressions;
        }

        $window = $this->calculateTimeWindow($timeWindow);
        
        $cap = FrequencyCap::where('campaign_id', $campaign->id)
            ->where('viewer_identifier', $viewerIdentifier)
            ->where('identifier_type', $identifierType)
            ->where('time_window', $timeWindow)
            ->where('window_start', '<=', now())
            ->where('window_end', '>=', now())
            ->first();

        if ($cap) {
            $canShow = $cap->impression_count < $maxImpressions;
            Cache::put($cacheKey, $cap->impression_count, $window['ttl']);
            return $canShow;
        }

        return true;
    }

    /**
     * Record an impression for frequency capping
     */
    public function recordImpression(
        Ad $ad,
        string $viewerIdentifier,
        string $identifierType = 'session',
        ?string $timeWindow = null
    ): void {
        $timeWindow = $timeWindow ?? $ad->metadata['frequency_cap']['time_window'] ?? 'day';
        $window = $this->calculateTimeWindow($timeWindow);

        // Update or create frequency cap record
        $cap = FrequencyCap::firstOrNew([
            'tenant_id' => $ad->tenant_id,
            'ad_id' => $ad->id,
            'viewer_identifier' => $viewerIdentifier,
            'identifier_type' => $identifierType,
            'time_window' => $timeWindow,
        ]);

        // Check if window has expired
        if ($cap->exists && ($cap->window_end < now() || $cap->window_start > now())) {
            // Reset for new window
            $cap->impression_count = 1;
            $cap->window_start = $window['start'];
            $cap->window_end = $window['end'];
        } else {
            // Increment existing count
            $cap->impression_count = ($cap->impression_count ?? 0) + 1;
            if (!$cap->exists) {
                $cap->window_start = $window['start'];
                $cap->window_end = $window['end'];
            }
        }

        $cap->save();

        // Update cache
        $cacheKey = $this->getCacheKey($ad->id, $viewerIdentifier, $identifierType, $timeWindow);
        Cache::put($cacheKey, $cap->impression_count, $window['ttl']);
    }

    /**
     * Record campaign-level impression
     */
    public function recordCampaignImpression(
        AdCampaign $campaign,
        string $viewerIdentifier,
        string $identifierType = 'session',
        ?string $timeWindow = null
    ): void {
        $timeWindow = $timeWindow ?? $campaign->metadata['frequency_cap']['time_window'] ?? 'day';
        $window = $this->calculateTimeWindow($timeWindow);

        $cap = FrequencyCap::firstOrNew([
            'tenant_id' => $campaign->tenant_id,
            'campaign_id' => $campaign->id,
            'viewer_identifier' => $viewerIdentifier,
            'identifier_type' => $identifierType,
            'time_window' => $timeWindow,
        ]);

        // Check if window has expired
        if ($cap->exists && ($cap->window_end < now() || $cap->window_start > now())) {
            // Reset for new window
            $cap->impression_count = 1;
            $cap->window_start = $window['start'];
            $cap->window_end = $window['end'];
        } else {
            // Increment existing count
            $cap->impression_count = ($cap->impression_count ?? 0) + 1;
            if (!$cap->exists) {
                $cap->window_start = $window['start'];
                $cap->window_end = $window['end'];
            }
        }

        $cap->save();

        $cacheKey = $this->getCampaignCacheKey($campaign->id, $viewerIdentifier, $identifierType, $timeWindow);
        Cache::put($cacheKey, $cap->impression_count, $window['ttl']);
    }

    /**
     * Calculate time window boundaries
     */
    protected function calculateTimeWindow(string $timeWindow): array
    {
        $now = Carbon::now();
        
        return match ($timeWindow) {
            'hour' => [
                'start' => $now->copy()->startOfHour(),
                'end' => $now->copy()->endOfHour(),
                'ttl' => 3600, // 1 hour
            ],
            'day' => [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
                'ttl' => 86400, // 24 hours
            ],
            'week' => [
                'start' => $now->copy()->startOfWeek(),
                'end' => $now->copy()->endOfWeek(),
                'ttl' => 604800, // 7 days
            ],
            'month' => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
                'ttl' => 2592000, // 30 days
            ],
            default => [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
                'ttl' => 86400,
            ],
        };
    }

    protected function getCacheKey(int $adId, string $viewerIdentifier, string $identifierType, string $timeWindow): string
    {
        return sprintf('freqcap:ad:%d:%s:%s:%s', $adId, $viewerIdentifier, $identifierType, $timeWindow);
    }

    protected function getCampaignCacheKey(int $campaignId, string $viewerIdentifier, string $identifierType, string $timeWindow): string
    {
        return sprintf('freqcap:campaign:%d:%s:%s:%s', $campaignId, $viewerIdentifier, $identifierType, $timeWindow);
    }
}

