<?php

namespace App\Console;

use App\Campaign;
use App\Jobs\CacheSegmentJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Schema;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        if (!Schema::hasTable("migrations")) {
            return;
        }

        // invalidate segments cache
        foreach (Campaign::whereActive(true)->cursor() as $campaign) {
            if (!$campaign->segment_id) {
                continue;
            }
            $schedule->job(new CacheSegmentJob($campaign->segment_id, true))
                ->hourly()
                ->withoutOverlapping();
        }

    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}
