<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiKeyController extends Controller
{
    public function index()
    {
        $tenants = Tenant::withCount('channels')->latest()->paginate(15);
        return view('api-keys.index', compact('tenants'));
    }

    public function regenerate(Tenant $tenant)
    {
        $tenant->update([
            'api_key' => 'fast_' . Str::random(32),
            'api_secret' => Str::random(64),
        ]);

        return redirect()->route('api-keys.index')
            ->with('success', 'API key regenerated successfully.');
    }

    public function show(Tenant $tenant)
    {
        return view('api-keys.show', compact('tenant'));
    }
}
