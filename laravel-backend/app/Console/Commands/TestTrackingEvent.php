<?php

namespace App\Console\Commands;

use App\Services\TrackingService;
use App\Models\Tenant;
use App\Models\Ad;
use Illuminate\Console\Command;

class TestTrackingEvent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tracking:test {--tenant_id=1} {--ad_id=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test sending a tracking event';

    protected TrackingService $trackingService;

    /**
     * Create a new command instance.
     */
    public function __construct(TrackingService $trackingService)
    {
        parent::__construct();
        $this->trackingService = $trackingService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenantId = $this->option('tenant_id');
        $adId = $this->option('ad_id');

        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            $this->error("Tenant ID {$tenantId} not found!");
            return 1;
        }

        $ad = Ad::find($adId);
        if (!$ad) {
            $this->error("Ad ID {$adId} not found!");
            return 1;
        }

        $this->info("Sending test tracking event...");
        $this->line("Tenant: {$tenant->name} (ID: {$tenant->id})");
        $this->line("Ad: {$ad->name} (ID: {$ad->id})");

        try {
            $event = $this->trackingService->recordEvent([
                'tenant_id' => $tenant->id,
                'ad_id' => $ad->id,
                'channel_id' => null,
                'event_type' => 'impression',
                'session_id' => 'test_' . uniqid(),
                'device_type' => 'test',
                'geo_country' => 'ID',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Test Command',
                'timestamp' => now('Asia/Jakarta'),
                'metadata' => ['source' => 'test_command'],
            ]);

            $this->info("✅ Tracking event created successfully!");
            $this->line("Event ID: {$event->id}");
            $this->line("Event Type: {$event->event_type}");
            $this->line("Timestamp: {$event->timestamp->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s')} WIB");
            $this->newLine();
            $this->info("Now run: php artisan reports:check");

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to create tracking event: " . $e->getMessage());
            return 1;
        }
    }
}

