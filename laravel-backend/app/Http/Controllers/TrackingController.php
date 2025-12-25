<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\TrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TrackingController extends Controller
{
    protected TrackingService $trackingService;

    public function __construct(TrackingService $trackingService)
    {
        $this->trackingService = $trackingService;
    }

    /**
     * POST /api/v1/tracking/events
     * Receive tracking events from Golang service or players
     */
    public function events(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'events' => 'required|array|min:1',
            'events.*.tenant_id' => 'required|integer|exists:tenants,id',
            'events.*.channel_id' => 'nullable|integer|exists:channels,id',
            'events.*.ad_id' => 'required|integer|exists:ads,id',
            'events.*.event_type' => 'required|in:impression,start,first_quartile,midpoint,third_quartile,complete,click,error',
            'events.*.session_id' => 'nullable|string|max:128',
            'events.*.device_type' => 'nullable|string|max:50',
            'events.*.geo_country' => 'nullable|string|size:2',
            'events.*.ip_address' => 'nullable|ip',
            'events.*.user_agent' => 'nullable|string',
            'events.*.timestamp' => 'nullable|date',
            'events.*.metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Invalid event data',
                    'details' => $validator->errors(),
                ],
            ], 422);
        }

        try {
            $processed = 0;
            $failed = 0;

            foreach ($request->events as $eventData) {
                try {
                    $this->trackingService->recordEvent($eventData);
                    $processed++;
                } catch (\Exception $e) {
                    Log::error('Failed to record tracking event', [
                        'event' => $eventData,
                        'error' => $e->getMessage(),
                    ]);
                    $failed++;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'processed' => $processed,
                    'failed' => $failed,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Tracking events error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'Failed to process tracking events',
                ],
            ], 500);
        }
    }
}

