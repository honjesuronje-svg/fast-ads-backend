<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\Tenant;
use Illuminate\Http\Request;

class ChannelController extends Controller
{
    public function index()
    {
        $channels = Channel::with('tenant')->latest()->paginate(15);
        return view('channels.index', compact('channels'));
    }

    public function create()
    {
        $tenants = Tenant::where('status', 'active')->get();
        return view('channels.create', compact('tenants'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'description' => 'nullable|string',
            'hls_manifest_url' => 'nullable|url',
            'ad_break_strategy' => 'nullable|string',
            'ad_break_interval_seconds' => 'nullable|integer|min:0',
            'enable_pre_roll' => 'nullable|boolean',
            'status' => 'required|in:active,inactive',
        ]);

        // Handle checkbox: if not present in request, set to false
        $validated['enable_pre_roll'] = $request->has('enable_pre_roll') && $request->input('enable_pre_roll') == '1';

        Channel::create($validated);

        return redirect()->route('channels.index')
            ->with('success', 'Channel created successfully.');
    }

    public function show(Channel $channel)
    {
        $channel->load('tenant');
        return view('channels.show', compact('channel'));
    }

    public function edit(Channel $channel)
    {
        $tenants = Tenant::where('status', 'active')->get();
        return view('channels.edit', compact('channel', 'tenants'));
    }

    public function update(Request $request, Channel $channel)
    {
        $validated = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'description' => 'nullable|string',
            'hls_manifest_url' => 'nullable|url',
            'ad_break_strategy' => 'nullable|in:static,scte35,hybrid',
            'ad_break_interval_seconds' => 'nullable|integer|min:0',
            'enable_pre_roll' => 'nullable|boolean',
            'status' => 'required|in:active,inactive',
        ]);

        // Set default values if not provided
        if (!isset($validated['ad_break_strategy'])) {
            $validated['ad_break_strategy'] = 'static';
        }
        if (!isset($validated['ad_break_interval_seconds'])) {
            $validated['ad_break_interval_seconds'] = 360; // Default 6 minutes
        }

        // Handle checkbox: if not present in request, set to false
        $validated['enable_pre_roll'] = $request->has('enable_pre_roll') && $request->input('enable_pre_roll') == '1';

        $channel->update($validated);

        return redirect()->route('channels.index')
            ->with('success', 'Channel updated successfully.');
    }

    public function destroy(Channel $channel)
    {
        $channel->delete();

        return redirect()->route('channels.index')
            ->with('success', 'Channel deleted successfully.');
    }
}
