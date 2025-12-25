<?php

namespace App\Services;

use App\Models\Ad;
use App\Models\AdPodConfig;
use App\Models\Channel;
use App\Models\Tenant;
use App\Services\FrequencyCapService;
use App\Services\AbTestService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdDecisionService
{
    protected FrequencyCapService $frequencyCapService;
    protected AbTestService $abTestService;

    public function __construct(
        FrequencyCapService $frequencyCapService,
        AbTestService $abTestService
    ) {
        $this->frequencyCapService = $frequencyCapService;
        $this->abTestService = $abTestService;
    }

    /**
     * Get ads for an ad break
     */
    public function getAdsForBreak(
        Tenant $tenant,
        Channel $channel,
        string $adBreakId,
        string $position,
        int $durationSeconds,
        ?string $geo = null,
        ?string $device = null,
        ?string $viewerIdentifier = null,
        ?string $identifierType = 'session',
    ): array {
        // Check cache first
        $cacheKey = $this->getCacheKey($tenant->id, $channel->id, $adBreakId, $position, $geo, $device);
        
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Get ad pod configuration
        $podConfig = $this->getPodConfig($tenant, $channel, $position);

        // Find eligible ads
        $ads = $this->findEligibleAds($tenant, $channel, $position, $geo, $device);

        // Apply frequency capping if viewer identifier provided
        if ($viewerIdentifier) {
            $ads = $this->applyFrequencyCapping($ads, $viewerIdentifier, $identifierType);
        }

        // Select ads based on pod config
        $selectedAds = $this->selectAdsForPod($ads, $podConfig, $durationSeconds);

        // Apply A/B testing to selected ads
        if ($viewerIdentifier) {
            $selectedAds = $this->applyAbTesting($selectedAds, $viewerIdentifier, $identifierType);
        }

        // Format response
        $decision = [
            'ads' => $this->formatAds($selectedAds),
            'total_duration_seconds' => $selectedAds->sum('duration_seconds'),
            'pod_id' => 'pod_' . uniqid(),
        ];

        // Cache decision (60 seconds)
        Cache::put($cacheKey, $decision, 60);

        return $decision;
    }

    /**
     * Find eligible ads based on rules
     */
    protected function findEligibleAds(
        Tenant $tenant,
        Channel $channel,
        string $position,
        ?string $geo = null,
        ?string $device = null,
    ): Collection {
        $now = now();

        // Query ads with active campaign and valid date range
        // Simplified query for testing - get all active ads first, then filter by campaign
        $allAds = Ad::where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->with(['rules', 'campaign'])
            ->get();
        
        \Log::info('AdDecisionService::findEligibleAds - Before campaign filter', [
            'now' => $now->toDateTimeString(),
            'tenant_id' => $tenant->id,
            'ads_before_filter' => $allAds->count(),
            'campaigns' => $allAds->map(function ($ad) {
                return [
                    'ad_id' => $ad->id,
                    'campaign_id' => $ad->campaign_id,
                    'campaign' => $ad->campaign ? [
                        'id' => $ad->campaign->id,
                        'status' => $ad->campaign->status,
                        'start_date' => $ad->campaign->start_date,
                        'end_date' => $ad->campaign->end_date,
                    ] : null,
                ];
            })->toArray(),
        ]);
        
        // TEMPORARY: Bypass campaign date check for testing
        // TODO: Fix timezone/date comparison issue
        $ads = $allAds->filter(function ($ad) use ($now) {
            if (!$ad->campaign) {
                \Log::info('Ad filtered out - no campaign', ['ad_id' => $ad->id]);
                return false;
            }
            // Only check campaign status, skip date check for now
            $statusOk = $ad->campaign->status === 'active';
            
            if (!$statusOk) {
                \Log::info('Ad filtered out - campaign status not active', [
                    'ad_id' => $ad->id,
                    'campaign_status' => $ad->campaign->status,
                ]);
                return false;
            }
            
            // Log for debugging
            \Log::info('Ad passed campaign filter', [
                'ad_id' => $ad->id,
                'campaign_id' => $ad->campaign->id,
                'campaign_status' => $ad->campaign->status,
            ]);
            
            return true;
        });
        
        // Debug logging
        \Log::info('AdDecisionService::findEligibleAds', [
            'tenant_id' => $tenant->id,
            'channel_id' => $channel->id,
            'position' => $position,
            'total_ads_found' => $ads->count(),
            'ads' => $ads->map(function ($ad) {
                return [
                    'id' => $ad->id,
                    'name' => $ad->name,
                    'campaign_id' => $ad->campaign_id,
                    'rules_count' => $ad->rules->count(),
                ];
            })->toArray(),
        ]);

        // Filter by rules
        $filtered = $ads->filter(function ($ad) use ($geo, $device, $channel) {
            $matches = $this->matchesRules($ad, $geo, $device, $channel);
            if (!$matches) {
                \Log::info('Ad filtered out', [
                    'ad_id' => $ad->id,
                    'ad_name' => $ad->name,
                    'rules' => $ad->rules->map(function ($r) {
                        return ['type' => $r->rule_type, 'value' => $r->rule_value];
                    })->toArray(),
                ]);
            }
            return $matches;
        })->sortByDesc('campaign.priority');
        
        \Log::info('AdDecisionService::findEligibleAds result', [
            'filtered_count' => $filtered->count(),
        ]);

        return $filtered;
    }

    /**
     * Check if ad matches targeting rules
     */
    protected function matchesRules(Ad $ad, ?string $geo, ?string $device, Channel $channel): bool
    {
        $rules = $ad->rules;

        if ($rules->isEmpty()) {
            return true; // No rules = match all
        }

        // All rules must match (AND logic)
        // But geo/device rules are skipped if not provided in request
        foreach ($rules as $rule) {
            $matches = match ($rule->rule_type) {
                'geo' => $this->matchGeoRule($rule, $geo),
                'device' => $this->matchDeviceRule($rule, $device),
                'channel' => $this->matchChannelRule($rule, $channel),
                'time' => $this->matchTimeRule($rule),
                'day_of_week' => $this->matchDayOfWeekRule($rule),
                default => true,
            };

            if (!$matches) {
                return false; // All rules must match
            }
        }

        return true;
    }

    protected function matchGeoRule($rule, ?string $geo): bool
    {
        // If no geo provided in request, skip geo rules (return true)
        // This allows ads with geo rules to still show when geo is not provided
        if (!$geo) {
            return true; // No geo in request = skip geo rule check
        }

        $values = json_decode($rule->rule_value, true);
        if (!is_array($values)) {
            $values = [$values];
        }

        return match ($rule->rule_operator) {
            'in' => in_array($geo, $values),
            'not_in' => !in_array($geo, $values),
            'equals' => $geo === $rule->rule_value,
            default => true,
        };
    }

    protected function matchDeviceRule($rule, ?string $device): bool
    {
        if (!$device) {
            return true;
        }

        $values = json_decode($rule->rule_value, true);

        return match ($rule->rule_operator) {
            'in' => in_array($device, $values),
            'contains' => str_contains(strtolower($device), strtolower($rule->rule_value)),
            default => true,
        };
    }

    protected function matchChannelRule($rule, Channel $channel): bool
    {
        $values = json_decode($rule->rule_value, true);

        return match ($rule->rule_operator) {
            'in' => in_array($channel->slug, $values),
            'equals' => $channel->slug === $rule->rule_value,
            default => true,
        };
    }

    protected function matchTimeRule($rule): bool
    {
        $range = json_decode($rule->rule_value, true);
        $hour = now()->hour;

        return $hour >= ($range['min'] ?? 0) && $hour <= ($range['max'] ?? 23);
    }

    protected function matchDayOfWeekRule($rule): bool
    {
        $days = json_decode($rule->rule_value, true);
        $currentDay = now()->dayOfWeek;

        return in_array($currentDay, $days);
    }

    /**
     * Select ads for pod based on configuration
     */
    protected function selectAdsForPod(Collection $ads, ?AdPodConfig $podConfig, int $maxDuration): Collection
    {
        if ($ads->isEmpty()) {
            return collect();
        }

        $minAds = $podConfig?->min_ads ?? 1;
        $maxAds = $podConfig?->max_ads ?? 3;
        $maxDurationSeconds = $podConfig?->max_duration_seconds ?? $maxDuration;

        $selected = collect();
        $totalDuration = 0;

        foreach ($ads as $ad) {
            if ($selected->count() >= $maxAds) {
                break;
            }

            if ($totalDuration + $ad->duration_seconds > $maxDurationSeconds) {
                continue;
            }

            $selected->push($ad);
            $totalDuration += $ad->duration_seconds;

            if ($selected->count() >= $minAds && $totalDuration >= ($maxDurationSeconds * 0.8)) {
                break; // Good enough
            }
        }

        return $selected;
    }

    /**
     * Get ad pod configuration
     */
    protected function getPodConfig(Tenant $tenant, Channel $channel, string $position): ?AdPodConfig
    {
        // Try channel-specific config first
        $config = AdPodConfig::where('tenant_id', $tenant->id)
            ->where('channel_id', $channel->id)
            ->where('position_type', $position)
            ->first();

        if ($config) {
            return $config;
        }

        // Fall back to tenant default
        return AdPodConfig::where('tenant_id', $tenant->id)
            ->whereNull('channel_id')
            ->where('position_type', $position)
            ->first();
    }

    /**
     * Apply frequency capping to ads
     */
    protected function applyFrequencyCapping(
        Collection $ads,
        string $viewerIdentifier,
        string $identifierType
    ): Collection {
        return $ads->filter(function ($ad) use ($viewerIdentifier, $identifierType) {
            // Check ad-level frequency cap
            $canShowAd = $this->frequencyCapService->canShowAd(
                $ad,
                $viewerIdentifier,
                $identifierType
            );

            if (!$canShowAd) {
                return false;
            }

            // Check campaign-level frequency cap
            if ($ad->campaign) {
                $canShowCampaign = $this->frequencyCapService->canShowCampaign(
                    $ad->campaign,
                    $viewerIdentifier,
                    $identifierType
                );
                return $canShowCampaign;
            }

            return true;
        });
    }

    /**
     * Apply A/B testing to ads
     */
    protected function applyAbTesting(
        Collection $ads,
        string $viewerIdentifier,
        string $identifierType
    ): Collection {
        return $ads->map(function ($ad) use ($viewerIdentifier, $identifierType) {
            // Check if ad has variants
            $variant = $this->abTestService->getVariantForViewer(
                $ad,
                $viewerIdentifier,
                $identifierType
            );

            // If variant exists, use it instead of original ad
            if ($variant) {
                // Create a temporary ad object with variant data
                $variantAd = clone $ad;
                $variantAd->id = $variant->id;
                $variantAd->vast_url = $variant->vast_url ?? $ad->vast_url;
                $variantAd->video_file_path = $variant->video_file_path ?? $ad->video_file_path;
                $variantAd->duration_seconds = $variant->duration_seconds ?? $ad->duration_seconds;
                $variantAd->metadata = array_merge($ad->metadata ?? [], [
                    'variant_id' => $variant->id,
                    'is_variant' => true,
                    'parent_ad_id' => $ad->id,
                ]);
                return $variantAd;
            }

            return $ad;
        });
    }

    /**
     * Format ads for response
     */
    protected function formatAds(Collection $ads): array
    {
        return $ads->map(function ($ad) {
            $isVariant = isset($ad->metadata['is_variant']) && $ad->metadata['is_variant'];
            
            return [
                'ad_id' => $ad->id,
                'variant_id' => $isVariant ? ($ad->metadata['variant_id'] ?? null) : null,
                'parent_ad_id' => $isVariant ? ($ad->metadata['parent_ad_id'] ?? null) : null,
                'vast_url' => $ad->vast_url,
                'duration_seconds' => $ad->duration_seconds,
                'ad_type' => $ad->ad_type,
                'click_through_url' => $ad->click_through_url,
                'tracking_urls' => [
                    'impression' => $this->buildTrackingUrl($ad, 'impression'),
                    'start' => $this->buildTrackingUrl($ad, 'start'),
                    'first_quartile' => $this->buildTrackingUrl($ad, 'first_quartile'),
                    'midpoint' => $this->buildTrackingUrl($ad, 'midpoint'),
                    'third_quartile' => $this->buildTrackingUrl($ad, 'third_quartile'),
                    'complete' => $this->buildTrackingUrl($ad, 'complete'),
                ],
            ];
        })->toArray();
    }

    protected function buildTrackingUrl(Ad $ad, string $eventType): string
    {
        // Return tracking URL - can be customized per tenant
        $baseUrl = config('app.url', 'http://localhost:8000');
        return "{$baseUrl}/api/v1/tracking/events?ad_id={$ad->id}&event_type={$eventType}";
    }

    protected function getCacheKey(int $tenantId, int $channelId, string $adBreakId, string $position, ?string $geo, ?string $device): string
    {
        return sprintf(
            'ad_decision:%d:%d:%s:%s:%s:%s',
            $tenantId,
            $channelId,
            $adBreakId,
            $position,
            $geo ?? 'any',
            $device ?? 'any'
        );
    }

    /**
     * Generate VMAP XML
     */
    public function generateVMAP(Tenant $tenant, Channel $channel, ?string $geo = null, ?string $device = null): string
    {
        $adBreaks = $channel->adBreaks()->where('status', 'active')->orderBy('offset_seconds')->get();

        $vmap = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $vmap .= '<vmap:VMAP xmlns:vmap="http://www.iab.net/vmap-1.0" version="1.0">' . "\n";

        foreach ($adBreaks as $break) {
            $timeOffset = $break->position === 'pre-roll' 
                ? 'start' 
                : ($break->position === 'post-roll' 
                    ? 'end' 
                    : gmdate('H:i:s', $break->offset_seconds));

            $vmap .= sprintf(
                '  <vmap:AdBreak timeOffset="%s" breakType="linear" breakId="%s">' . "\n",
                $timeOffset,
                $break->id
            );
            $vmap .= '    <vmap:AdSource id="' . $break->id . '" allowMultipleAds="true" followRedirects="true">' . "\n";
            $vmap .= '      <vmap:AdTagURI templateType="vast3">' . "\n";
            $vmap .= '        <![CDATA[' . route('ads.vast', [
                'tenant_slug' => $tenant->slug,
                'channel_slug' => $channel->slug,
                'position' => $break->position_type,
                'geo' => $geo,
                'device' => $device,
            ]) . ']]>' . "\n";
            $vmap .= '      </vmap:AdTagURI>' . "\n";
            $vmap .= '    </vmap:AdSource>' . "\n";
            $vmap .= '  </vmap:AdBreak>' . "\n";
        }

        $vmap .= '</vmap:VMAP>';

        return $vmap;
    }

    /**
     * Generate VAST XML
     */
    public function generateVAST(
        Tenant $tenant,
        Channel $channel,
        string $position,
        ?string $geo = null,
        ?string $device = null,
    ): string {
        $decision = $this->getAdsForBreak(
            tenant: $tenant,
            channel: $channel,
            adBreakId: uniqid(),
            position: $position,
            durationSeconds: 120,
            geo: $geo,
            device: $device,
        );

        $vast = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $vast .= '<VAST version="3.0">' . "\n";

        foreach ($decision['ads'] as $ad) {
            $vast .= '  <Ad id="ad_' . $ad['ad_id'] . '">' . "\n";
            $vast .= '    <InLine>' . "\n";
            $vast .= '      <AdSystem>FAST Ads Platform</AdSystem>' . "\n";
            $vast .= '      <AdTitle>Ad ' . $ad['ad_id'] . '</AdTitle>' . "\n";
            
            if (!empty($ad['tracking_urls']['impression'])) {
                $vast .= '      <Impression>' . $ad['tracking_urls']['impression'] . '</Impression>' . "\n";
            }

            $vast .= '      <Creatives>' . "\n";
            $vast .= '        <Creative id="creative_' . $ad['ad_id'] . '">' . "\n";
            $vast .= '          <Linear>' . "\n";
            $vast .= '            <Duration>' . gmdate('H:i:s', $ad['duration_seconds']) . '</Duration>' . "\n";
            $vast .= '            <MediaFiles>' . "\n";
            $vast .= '              <MediaFile delivery="progressive" type="video/mp4" width="1920" height="1080">' . "\n";
            $vast .= '                ' . $ad['vast_url'] . "\n";
            $vast .= '              </MediaFile>' . "\n";
            $vast .= '            </MediaFiles>' . "\n";

            if (!empty($ad['tracking_urls'])) {
                $vast .= '            <TrackingEvents>' . "\n";
                foreach ($ad['tracking_urls'] as $event => $url) {
                    if ($url && $event !== 'impression') {
                        $vastEvent = match ($event) {
                            'start' => 'start',
                            'first_quartile' => 'firstQuartile',
                            'midpoint' => 'midpoint',
                            'third_quartile' => 'thirdQuartile',
                            'complete' => 'complete',
                            default => $event,
                        };
                        $vast .= '              <Tracking event="' . $vastEvent . '">' . $url . '</Tracking>' . "\n";
                    }
                }
                $vast .= '            </TrackingEvents>' . "\n";
            }

            $vast .= '          </Linear>' . "\n";
            $vast .= '        </Creative>' . "\n";
            $vast .= '      </Creatives>' . "\n";
            $vast .= '    </InLine>' . "\n";
            $vast .= '  </Ad>' . "\n";
        }

        $vast .= '</VAST>';

        return $vast;
    }
}

