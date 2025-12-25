<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tenants = Tenant::latest()->paginate(15);
        return view('tenants.index', compact('tenants'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('tenants.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:tenants,slug|regex:/^[a-z0-9-]+$/',
            'status' => 'required|in:active,inactive,suspended',
            'allowed_domains' => 'nullable|string',
            'rate_limit_per_minute' => 'nullable|integer|min:1|max:10000',
        ]);

        // Generate API key and secret
        $validated['api_key'] = 'fast_' . $validated['slug'] . '_' . bin2hex(random_bytes(16));
        $validated['api_secret'] = bin2hex(random_bytes(32));

        // Parse allowed_domains if provided
        if (isset($validated['allowed_domains']) && !empty($validated['allowed_domains'])) {
            $domains = array_filter(array_map('trim', explode(',', $validated['allowed_domains'])));
            $validated['allowed_domains'] = !empty($domains) ? $domains : null;
        } else {
            $validated['allowed_domains'] = null;
        }

        // Set default rate limit if not provided
        if (!isset($validated['rate_limit_per_minute'])) {
            $validated['rate_limit_per_minute'] = 1000;
        }

        Tenant::create($validated);

        return redirect()->route('tenants.index')
            ->with('success', 'Tenant created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Tenant $tenant)
    {
        return view('tenants.show', compact('tenant'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Tenant $tenant)
    {
        return view('tenants.edit', compact('tenant'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:tenants,slug,' . $tenant->id . '|regex:/^[a-z0-9-]+$/',
            'status' => 'required|in:active,inactive,suspended',
            'allowed_domains' => 'nullable|string',
            'rate_limit_per_minute' => 'nullable|integer|min:1|max:10000',
        ]);

        // Parse allowed_domains if provided
        if (isset($validated['allowed_domains']) && !empty($validated['allowed_domains'])) {
            $domains = array_filter(array_map('trim', explode(',', $validated['allowed_domains'])));
            $validated['allowed_domains'] = !empty($domains) ? $domains : null;
        } else {
            $validated['allowed_domains'] = null;
        }

        $tenant->update($validated);

        return redirect()->route('tenants.index')
            ->with('success', 'Tenant updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tenant $tenant)
    {
        $tenant->delete();

        return redirect()->route('tenants.index')
            ->with('success', 'Tenant deleted successfully.');
    }
}
