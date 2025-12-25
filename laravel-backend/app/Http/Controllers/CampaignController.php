<?php

namespace App\Http\Controllers;

use App\Models\AdCampaign;
use App\Models\Tenant;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function index()
    {
        $campaigns = AdCampaign::with('tenant')->latest()->paginate(15);
        return view('campaigns.index', compact('campaigns'));
    }

    public function create()
    {
        $tenants = Tenant::where('status', 'active')->get();
        return view('campaigns.create', compact('tenants'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'budget' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        AdCampaign::create($validated);

        return redirect()->route('campaigns.index')
            ->with('success', 'Campaign created successfully.');
    }

    public function show(AdCampaign $campaign)
    {
        $campaign->load('tenant');
        return view('campaigns.show', compact('campaign'));
    }

    public function edit(AdCampaign $campaign)
    {
        $tenants = Tenant::where('status', 'active')->get();
        return view('campaigns.edit', compact('campaign', 'tenants'));
    }

    public function update(Request $request, AdCampaign $campaign)
    {
        $validated = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'budget' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        $campaign->update($validated);

        return redirect()->route('campaigns.index')
            ->with('success', 'Campaign updated successfully.');
    }

    public function destroy(AdCampaign $campaign)
    {
        $campaign->delete();

        return redirect()->route('campaigns.index')
            ->with('success', 'Campaign deleted successfully.');
    }
}
