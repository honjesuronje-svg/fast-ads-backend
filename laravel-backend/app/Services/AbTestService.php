<?php

namespace App\Services;

use App\Models\Ad;
use App\Models\AdVariant;
use App\Models\AbTestAssignment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class AbTestService
{
    /**
     * Get variant for a viewer (assign if not already assigned)
     */
    public function getVariantForViewer(
        Ad $ad,
        string $viewerIdentifier,
        string $identifierType = 'session'
    ): ?AdVariant {
        // Check if viewer already has an assignment
        $assignment = AbTestAssignment::where('ad_id', $ad->id)
            ->where('viewer_identifier', $viewerIdentifier)
            ->where('identifier_type', $identifierType)
            ->first();

        if ($assignment) {
            return $assignment->variant;
        }

        // Get active variants for this ad
        $variants = AdVariant::where('ad_id', $ad->id)
            ->where('status', 'active')
            ->orderByDesc('priority')
            ->get();

        if ($variants->isEmpty()) {
            return null; // No variants = use original ad
        }

        // Assign variant based on traffic percentage
        $variant = $this->assignVariant($variants, $viewerIdentifier);

        if ($variant) {
            // Store assignment
            AbTestAssignment::create([
                'tenant_id' => $ad->tenant_id,
                'ad_id' => $ad->id,
                'variant_id' => $variant->id,
                'viewer_identifier' => $viewerIdentifier,
                'identifier_type' => $identifierType,
                'assigned_at' => now(),
            ]);
        }

        return $variant;
    }

    /**
     * Assign variant using consistent hashing based on viewer identifier
     */
    protected function assignVariant(Collection $variants, string $viewerIdentifier): ?AdVariant
    {
        if ($variants->isEmpty()) {
            return null;
        }

        // Use consistent hashing to ensure same viewer always gets same variant
        $hash = crc32($viewerIdentifier);
        $totalPercentage = $variants->sum('traffic_percentage');
        
        // Normalize percentages if they don't sum to 100
        if ($totalPercentage !== 100) {
            $variants = $variants->map(function ($variant) use ($totalPercentage) {
                $variant->traffic_percentage = ($variant->traffic_percentage / $totalPercentage) * 100;
                return $variant;
            });
        }

        // Assign based on hash modulo
        $hashValue = abs($hash) % 100;
        $cumulative = 0;

        foreach ($variants as $variant) {
            $cumulative += $variant->traffic_percentage;
            if ($hashValue < $cumulative) {
                return $variant;
            }
        }

        // Fallback to first variant
        return $variants->first();
    }

    /**
     * Get all variants for an ad
     */
    public function getVariants(Ad $ad): Collection
    {
        return AdVariant::where('ad_id', $ad->id)
            ->where('status', 'active')
            ->orderByDesc('priority')
            ->get();
    }

    /**
     * Get variant statistics
     */
    public function getVariantStats(int $variantId, ?string $startDate = null, ?string $endDate = null): array
    {
        // This would typically query the ad_reports table
        // For now, return basic structure
        return [
            'variant_id' => $variantId,
            'impressions' => 0,
            'completions' => 0,
            'completion_rate' => 0,
            'clicks' => 0,
            'click_through_rate' => 0,
        ];
    }
}

