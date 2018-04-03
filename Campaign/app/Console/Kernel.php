<?php

namespace App\Console;

use App\Campaign;
use App\Jobs\CacheSegmentJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Carbon;
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
        try {
            $campaigns = Campaign::selectRaw('campaigns.*')
                ->join('schedules', 'campaigns.id', '=', 'campaign_id')
                ->where(function (\Illuminate\Database\Eloquent\Builder $query) {
                    $query
                        ->whereNull('end_time')
                        ->orWhere('end_time', '>=', Carbon::now());
                })
                ->whereIn('status', [\App\Schedule::STATUS_READY, \App\Schedule::STATUS_EXECUTED, \App\Schedule::STATUS_PAUSED])->cursor();

            /** @var Campaign $campaign */
            foreach ($campaigns as $campaign) {
                foreach ($campaign->segments as $campaignSegment) {
                    $schedule->job(new CacheSegmentJob($campaignSegment, true))
                        ->hourly()
                        ->withoutOverlapping();

                    $schedule->job(new CacheSegmentJob($campaignSegment, false))
                        ->everyMinute()
                        ->withoutOverlapping();
                }
            }
        } catch (\PDOException $e) {
            // no action, the tables are not ready yet
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
