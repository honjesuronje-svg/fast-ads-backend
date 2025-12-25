<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\Channel;
use App\Models\Ad;
use App\Models\AdCampaign;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index()
    {
        $stats = [
            'tenants' => Tenant::count(),
            'channels' => Channel::count(),
            'ads' => Ad::count(),
            'campaigns' => AdCampaign::count(),
            'active_ads' => Ad::where('status', 'active')->count(),
            'active_campaigns' => AdCampaign::where('status', 'active')->count(),
        ];

        return view('dashboard.index', compact('stats'));
    }
}
