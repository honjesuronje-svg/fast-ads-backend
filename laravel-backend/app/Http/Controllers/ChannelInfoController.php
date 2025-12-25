<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\Tenant;
use Illuminate\Http\Request;

class ChannelInfoController extends Controller
{
    /**
     * Get channel information by tenant slug and channel slug
     * Used by Golang SSAI service
     */
    public function show($tenantSlug, $channelSlug)
    {
        $tenant = Tenant::where('slug', $tenantSlug)->where('status', 'active')->first();
        
        if (!$tenant) {
            return response()->json([
                'success' => false,
                'error' => 'Tenant not found',
            ], 404);
        }

        $channel = Channel::where('tenant_id', $tenant->id)
            ->where('slug', $channelSlug)
            ->where('status', 'active')
            ->first();

        if (!$channel) {
            return response()->json([
                'success' => false,
                'error' => 'Channel not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $channel->id,
                'tenant_id' => $channel->tenant_id,
                'name' => $channel->name,
                'slug' => $channel->slug,
                'hls_manifest_url' => $channel->hls_manifest_url,
                'ad_break_strategy' => $channel->ad_break_strategy ?? 'static',
                'ad_break_interval_seconds' => $channel->ad_break_interval_seconds ?? 360,
                'enable_pre_roll' => $channel->enable_pre_roll ?? false,
                'status' => $channel->status,
            ],
        ]);
    }
}

