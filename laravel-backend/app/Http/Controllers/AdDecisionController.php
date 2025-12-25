<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\Tenant;
use App\Services\AdDecisionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdDecisionController extends Controller
{
    protected AdDecisionService $adDecisionService;

    public function __construct(AdDecisionService $adDecisionService)
    {
        $this->adDecisionService = $adDecisionService;
    }

    /**
     * POST /api/v1/ads/decision
     * Called by Golang service to get ads for an ad break
     */
    public function decision(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|integer',
            'channel' => 'required|string',
            'ad_break_id' => 'required|string',
            'position' => 'required|in:pre-roll,mid-roll,post-roll',
            'duration_seconds' => 'required|integer|min:1|max:600',
            'geo' => 'nullable|string|size:2',
            'device' => 'nullable|string|max:100',
            'timestamp' => 'nullable|date',
            'viewer_identifier' => 'nullable|string|max:255', // For frequency capping & A/B testing
            'identifier_type' => 'nullable|string|in:session,device,user',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Invalid request data',
                    'details' => $validator->errors(),
                ],
            ], 422);
        }

        try {
            $tenant = Tenant::findOrFail($request->tenant_id);
            
            // Verify API key matches tenant
            $apiKey = $request->header('X-API-Key');
            if ($tenant->api_key !== $apiKey) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_API_KEY',
                        'message' => 'API key does not match tenant',
                    ],
                ], 401);
            }

            // Find channel
            $channel = Channel::where('tenant_id', $tenant->id)
                ->where('slug', $request->channel)
                ->where('status', 'active')
                ->firstOrFail();

            // Get ads decision
            $decision = $this->adDecisionService->getAdsForBreak(
                tenant: $tenant,
                channel: $channel,
                adBreakId: $request->ad_break_id,
                position: $request->position,
                durationSeconds: $request->duration_seconds,
                geo: $request->geo,
                device: $request->device,
                viewerIdentifier: $request->viewer_identifier,
                identifierType: $request->identifier_type ?? 'session',
            );

            // Log decision for analytics
            Log::info('Ad decision made', [
                'tenant_id' => $tenant->id,
                'channel_id' => $channel->id,
                'ad_break_id' => $request->ad_break_id,
                'ads_count' => count($decision['ads']),
            ]);

            return response()->json([
                'success' => true,
                'data' => $decision,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CHANNEL_NOT_FOUND',
                    'message' => 'Channel not found or inactive',
                ],
            ], 404);

        } catch (\Exception $e) {
            Log::error('Ad decision error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'Failed to make ad decision',
                ],
            ], 500);
        }
    }

    /**
     * GET /api/v1/ads/vmap/{tenant_slug}/{channel_slug}
     * Generate VMAP for client-side ad insertion
     */
    public function vmap(Request $request, string $tenantSlug, string $channelSlug)
    {
        $tenant = Tenant::where('slug', $tenantSlug)
            ->where('status', 'active')
            ->firstOrFail();

        $channel = Channel::where('tenant_id', $tenant->id)
            ->where('slug', $channelSlug)
            ->where('status', 'active')
            ->firstOrFail();

        $geo = $request->query('geo');
        $device = $request->query('device');

        $vmap = $this->adDecisionService->generateVMAP(
            tenant: $tenant,
            channel: $channel,
            geo: $geo,
            device: $device,
        );

        return response($vmap, 200)
            ->header('Content-Type', 'application/vmap+xml');
    }

    /**
     * GET /api/v1/ads/vast/{tenant_slug}/{channel_slug}
     * Generate VAST XML for a specific ad break
     */
    public function vast(Request $request, string $tenantSlug, string $channelSlug)
    {
        $request->validate([
            'position' => 'required|in:pre-roll,mid-roll,post-roll',
        ]);

        $tenant = Tenant::where('slug', $tenantSlug)
            ->where('status', 'active')
            ->firstOrFail();

        $channel = Channel::where('tenant_id', $tenant->id)
            ->where('slug', $channelSlug)
            ->where('status', 'active')
            ->firstOrFail();

        $geo = $request->query('geo');
        $device = $request->query('device');
        $position = $request->query('position');

        $vast = $this->adDecisionService->generateVAST(
            tenant: $tenant,
            channel: $channel,
            position: $position,
            geo: $geo,
            device: $device,
        );

        return response($vast, 200)
            ->header('Content-Type', 'application/vast+xml');
    }
}

