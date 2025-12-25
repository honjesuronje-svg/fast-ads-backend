<?php

namespace App\Console\Commands;

use App\Services\ReportingService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class AggregateReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:aggregate {date?} {--date=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aggregate tracking events into reports for a specific date';

    protected ReportingService $reportingService;

    /**
     * Create a new command instance.
     */
    public function __construct(ReportingService $reportingService)
    {
        parent::__construct();
        $this->reportingService = $reportingService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Support both argument and option for date
        $dateInput = $this->argument('date') ?? $this->option('date');
        $date = $dateInput 
            ? Carbon::parse($dateInput)->setTimezone('Asia/Jakarta')
            : Carbon::yesterday('Asia/Jakarta');

        $this->info("Aggregating reports for date: {$date->toDateString()} (WIB)");

        try {
            $this->reportingService->aggregateTrackingEvents($date->toDateString());
            $this->info("Successfully aggregated reports for {$date->toDateString()}");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to aggregate reports: " . $e->getMessage());
            return 1;
        }
    }
}

