<?php

namespace App\Console;

use App\Console\Commands\AggregateArticlesViews;
use App\Console\Commands\AggregateConversionEvents;
use App\Console\Commands\AggregatePageviewLoadJob;
use App\Console\Commands\AggregatePageviewTimespentJob;
use App\Console\Commands\CompressSnapshots;
use App\Console\Commands\ComputeAuthorsSegments;
use App\Console\Commands\DeleteOldAggregations;
use App\Console\Commands\ProcessPageviewSessions;
use App\Console\Commands\SendNewslettersCommand;
use App\Console\Commands\CompressAggregations;
use App\Console\Commands\SnapshotArticlesViews;
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

        // Related commands are put into private functions

        $this->aggregations($schedule);
        $this->concurrentsSnapshots($schedule);
        $this->authorSegments($schedule);

        // All other unrelated commands

        $schedule->command(SendNewslettersCommand::COMMAND)
            ->everyMinute()
            ->appendOutputTo(storage_path('logs/send_newsletters.log'));

        // Aggregate any conversion events that weren't aggregated before due to Segments API fail
        // or other unexpected event
        $schedule->command(AggregateConversionEvents::COMMAND)
            ->dailyAt('3:30')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/aggregate_conversion_events.log'));
    }

    /**
     * Snapshot of concurrents for dashboard + regular compression
     * @param Schedule $schedule
     */
    private function concurrentsSnapshots(Schedule $schedule)
    {
        $schedule->command(SnapshotArticlesViews::COMMAND)
            ->everyMinute()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/snapshot_articles_views.log'));

        $schedule->command(CompressSnapshots::COMMAND)
            ->dailyAt('02:30')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/compress_snapshots.log'));
    }

    /**
     * Author segments related aggregation and processing
     * @param Schedule $schedule
     */
    private function authorSegments(Schedule $schedule)
    {
        $schedule->command(AggregateArticlesViews::COMMAND, ['--skip-temp-aggregation'])
            ->dailyAt('01:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/aggregate_article_views.log'));

        $schedule->command(ComputeAuthorsSegments::COMMAND)
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/compute_author_segments.log'));
    }

    /**
     * Pageviews, timespent and session pageviews aggregation and cleanups
     * @param Schedule $schedule
     */
    private function aggregations(Schedule $schedule)
    {
        // Aggregates current hour (may not be completed yet)
        $schedule->command(AggregatePageviewLoadJob::COMMAND, ["--now='+1 hour'"])
            ->everyMinute()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/aggregate_pageview_load.log'));

        $schedule->command(AggregatePageviewTimespentJob::COMMAND, ["--now='+1 hour'"])
            ->everyMinute()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/aggregate_pageview_timespent.log'));

        // Aggregates last full hour only once
        $schedule->command(AggregatePageviewLoadJob::COMMAND)
            ->hourlyAt(1) // 1 minute after, so we make sure previous hour is completed
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/aggregate_pageview_load.log'));

        $schedule->command(AggregatePageviewTimespentJob::COMMAND)
            ->hourlyAt(1) //
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/aggregate_pageview_timespent.log'));
        
        $schedule->command(ProcessPageviewSessions::COMMAND)
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/process_pageview_sessions.log'));

        $schedule->command(DeleteOldAggregations::COMMAND)
            ->dailyAt('00:10')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/delete_old_aggregations.log'));

        $schedule->command(CompressAggregations::COMMAND)
            ->dailyAt('00:20')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/compress_aggregations.log'));
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
        $this->load(__DIR__.'/Commands');
    }
}
