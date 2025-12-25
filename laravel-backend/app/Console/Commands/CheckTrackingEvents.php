<?php

namespace App\Console\Commands;

use App\Models\TrackingEvent;
use App\Models\AdReport;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CheckTrackingEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:check {--date=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check tracking events and reports for a specific date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Default to today if no date specified
        $date = $this->option('date') 
            ? Carbon::parse($this->option('date'))->setTimezone('Asia/Jakarta')
            : Carbon::today('Asia/Jakarta');

        $this->info("Checking events for date: {$date->toDateString()} (WIB)");
        $this->newLine();

        // Convert to UTC for database query
        $startOfDay = $date->copy()->startOfDay()->setTimezone('UTC');
        $endOfDay = $date->copy()->endOfDay()->setTimezone('UTC');

        // Check tracking events
        $events = TrackingEvent::whereBetween('timestamp', [$startOfDay, $endOfDay])->get();
        
        $this->info("📊 Tracking Events:");
        $this->line("Total events: " . $events->count());
        
        if ($events->count() > 0) {
            $this->line("Impressions: " . $events->where('event_type', 'impression')->count());
            $this->line("Starts: " . $events->where('event_type', 'start')->count());
            $this->line("Completions: " . $events->where('event_type', 'complete')->count());
            $this->line("Clicks: " . $events->where('event_type', 'click')->count());
            
            $this->newLine();
            $this->info("Recent events (last 5):");
            $events->take(5)->each(function ($event) {
                $this->line("  - {$event->event_type} | Ad ID: {$event->ad_id} | Time: {$event->timestamp->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s')} WIB");
            });
        } else {
            $this->warn("⚠️  No tracking events found for this date!");
            $this->line("Make sure tracking events are being sent to: POST /api/v1/tracking/events");
        }

        $this->newLine();

        // Check reports
        $reports = AdReport::whereDate('report_date', $date->toDateString())->get();
        
        $this->info("📈 Reports:");
        $this->line("Total reports: " . $reports->count());
        
        if ($reports->count() > 0) {
            $totalImpressions = $reports->sum('impressions');
            $totalCompletions = $reports->sum('completions');
            $this->line("Total impressions: " . $totalImpressions);
            $this->line("Total completions: " . $totalCompletions);
            
            if ($totalImpressions == 0 && $events->where('event_type', 'impression')->count() > 0) {
                $this->warn("⚠️  Reports show 0 impressions but tracking events exist!");
                $this->line("Run: php artisan reports:aggregate --date={$date->toDateString()}");
            }
        } else {
            $this->warn("⚠️  No reports found for this date!");
            if ($events->count() > 0) {
                $this->line("Run aggregation: php artisan reports:aggregate --date={$date->toDateString()}");
            }
        }

        return 0;
    }
}

