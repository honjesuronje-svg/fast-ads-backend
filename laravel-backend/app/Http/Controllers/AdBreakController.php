<?php

namespace App\Http\Controllers;

use App\Models\AdBreak;
use App\Models\Channel;
use Illuminate\Http\Request;

class AdBreakController extends Controller
{
    public function index(Channel $channel)
    {
        $adBreaks = $channel->adBreaks()->orderBy('position_type')->orderBy('offset_seconds')->get();
        return view('ad-breaks.index', compact('channel', 'adBreaks'));
    }

    public function create(Channel $channel)
    {
        return view('ad-breaks.create', compact('channel'));
    }

    public function store(Request $request, Channel $channel)
    {
        $validated = $request->validate([
            'position_type' => 'required|in:pre-roll,mid-roll,post-roll',
            'offset_seconds' => 'required_if:position_type,mid-roll|nullable|integer|min:0',
            'duration_seconds' => 'required|integer|min:30|max:300',
            'priority' => 'nullable|integer|min:0|max:100',
            'status' => 'required|in:active,inactive',
        ]);

        // For pre-roll and post-roll, offset is 0
        if ($validated['position_type'] === 'pre-roll' || $validated['position_type'] === 'post-roll') {
            $validated['offset_seconds'] = 0;
        }

        $validated['channel_id'] = $channel->id;
        $validated['priority'] = $validated['priority'] ?? 0;

        AdBreak::create($validated);

        return redirect()->route('channels.show', $channel)
            ->with('success', 'Ad break created successfully.');
    }

    public function edit(Channel $channel, AdBreak $adBreak)
    {
        return view('ad-breaks.edit', compact('channel', 'adBreak'));
    }

    public function update(Request $request, Channel $channel, AdBreak $adBreak)
    {
        $validated = $request->validate([
            'position_type' => 'required|in:pre-roll,mid-roll,post-roll',
            'offset_seconds' => 'required_if:position_type,mid-roll|nullable|integer|min:0',
            'duration_seconds' => 'required|integer|min:30|max:300',
            'priority' => 'nullable|integer|min:0|max:100',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validated['position_type'] === 'pre-roll' || $validated['position_type'] === 'post-roll') {
            $validated['offset_seconds'] = 0;
        }

        $adBreak->update($validated);

        return redirect()->route('channels.show', $channel)
            ->with('success', 'Ad break updated successfully.');
    }

    public function destroy(Channel $channel, AdBreak $adBreak)
    {
        $adBreak->delete();

        return redirect()->route('channels.show', $channel)
            ->with('success', 'Ad break deleted successfully.');
    }
}

