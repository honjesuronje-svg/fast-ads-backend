<?php

namespace Database\Seeders;

use App\Models\Ad;
use App\Models\AdBreak;
use App\Models\AdCampaign;
use App\Models\AdPodConfig;
use App\Models\AdRule;
use App\Models\Channel;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create sample tenant
        $tenant = Tenant::create([
            'name' => 'OTT Platform A',
            'slug' => 'ott_a',
            'api_key' => 'test_api_key_123',
            'api_secret' => Hash::make('secret'),
            'status' => 'active',
            'allowed_domains' => ['https://app.otta.com'],
            'rate_limit_per_minute' => 1000,
        ]);

        // Create sample channel
        $channel = Channel::create([
            'tenant_id' => $tenant->id,
            'name' => 'News Channel',
            'slug' => 'news',
            'description' => '24/7 News coverage',
            'hls_manifest_url' => 'https://cdn.example.com/news/master.m3u8',
            'ad_break_strategy' => 'static',
            'ad_break_interval_seconds' => 360,
            'status' => 'active',
        ]);

        // Create ad breaks
        AdBreak::create([
            'channel_id' => $channel->id,
            'position_type' => 'pre-roll',
            'offset_seconds' => 0,
            'duration_seconds' => 30,
            'priority' => 0,
            'status' => 'active',
        ]);

        AdBreak::create([
            'channel_id' => $channel->id,
            'position_type' => 'mid-roll',
            'offset_seconds' => 180,
            'duration_seconds' => 60,
            'priority' => 0,
            'status' => 'active',
        ]);

        // Create campaign
        $campaign = AdCampaign::create([
            'tenant_id' => $tenant->id,
            'name' => 'Q1 2024 Campaign',
            'description' => 'First quarter advertising campaign',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'status' => 'active',
            'priority' => 10,
        ]);

        // Create ads
        $ad1 = Ad::create([
            'tenant_id' => $tenant->id,
            'campaign_id' => $campaign->id,
            'name' => 'Brand Ad 1',
            'vast_url' => 'https://adserver.com/vast.xml',
            'duration_seconds' => 30,
            'ad_type' => 'linear',
            'click_through_url' => 'https://advertiser.com',
            'status' => 'active',
        ]);

        $ad2 = Ad::create([
            'tenant_id' => $tenant->id,
            'campaign_id' => $campaign->id,
            'name' => 'Brand Ad 2',
            'vast_url' => 'https://adserver.com/vast2.xml',
            'duration_seconds' => 15,
            'ad_type' => 'linear',
            'status' => 'active',
        ]);

        // Create ad rules (geo targeting)
        AdRule::create([
            'ad_id' => $ad1->id,
            'rule_type' => 'geo',
            'rule_operator' => 'in',
            'rule_value' => json_encode(['US', 'CA', 'MX']),
            'priority' => 0,
        ]);

        // Create ad pod configs
        AdPodConfig::create([
            'tenant_id' => $tenant->id,
            'channel_id' => $channel->id,
            'position_type' => 'pre-roll',
            'min_ads' => 1,
            'max_ads' => 1,
            'max_duration_seconds' => 30,
            'fill_strategy' => 'best_effort',
        ]);

        AdPodConfig::create([
            'tenant_id' => $tenant->id,
            'channel_id' => $channel->id,
            'position_type' => 'mid-roll',
            'min_ads' => 2,
            'max_ads' => 4,
            'max_duration_seconds' => 120,
            'fill_strategy' => 'best_effort',
        ]);

        $this->command->info('Sample data seeded successfully!');
        $this->command->info("Tenant API Key: test_api_key_123");
    }
}

