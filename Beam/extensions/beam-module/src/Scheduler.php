<?php

namespace Remp\BeamModule;

use Illuminate\Console\Scheduling\Schedule;
use Remp\BeamModule\Console\Commands\AggregateArticlesViews;
use Remp\BeamModule\Console\Commands\AggregateConversionEvents;
use Remp\BeamModule\Console\Commands\AggregatePageviews;
use Remp\BeamModule\Console\Commands\CompressAggregations;
use Remp\BeamModule\Console\Commands\CompressSnapshots;
use Remp\BeamModule\Console\Commands\ComputeAuthorsSegments;
use Remp\BeamModule\Console\Commands\ComputeSectionSegments;
use Remp\BeamModule\Console\Commands\DashboardRefresh;
use Remp\BeamModule\Console\Commands\DeleteOldAggregations;
use Remp\BeamModule\Console\Commands\SendNewslettersCommand;
use Remp\BeamModule\Console\Commands\SnapshotArticlesViews;

class Scheduler
{
    public function schedule(Schedule $schedule)
    {
        // Related commands are put into private functions

        $this->concurrentsSnapshots($schedule);
        $this->aggregations($schedule);
        $this->authorSegments($schedule);
        $this->dashboard($schedule);

        // All other unrelated commands

        $schedule->command(SendNewslettersCommand::COMMAND)
            ->everyMinute()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/send_newsletters.log'));

        // Aggregate any conversion events that weren't aggregated before due to Segments API fail
        // or other unexpected event
        $schedule->command(AggregateConversionEvents::COMMAND)
            ->dailyAt('3:30')
            ->runInBackground()
            ->withoutOverlapping(config('system.commands_overlapping_expires_at'))
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
            ->runInBackground()
            ->withoutOverlapping(config('system.commands_overlapping_expires_at'))
            ->appendOutputTo(storage_path('logs/snapshot_articles_views.log'));

        $schedule->command(CompressSnapshots::COMMAND)
            ->dailyAt('02:30')
            ->runInBackground()
            ->withoutOverlapping(config('system.commands_overlapping_expires_at'))
            ->appendOutputTo(storage_path('logs/compress_snapshots.log'));
    }

    /**
     * Author segments related aggregation and processing
     * @param Schedule $schedule
     */
    private function authorSegments(Schedule $schedule)
    {
        $schedule->command(AggregateArticlesViews::COMMAND, [
            '--skip-temp-aggregation',
            '--step' => config('system.commands.aggregate_article_views.default_step'),
        ])
            ->dailyAt('01:00')
            ->runInBackground()
            ->withoutOverlapping(config('system.commands_overlapping_expires_at'))
            ->appendOutputTo(storage_path('logs/aggregate_article_views.log'));

        $schedule->command(ComputeAuthorsSegments::COMMAND)
            ->dailyAt('02:00')
            ->runInBackground()
            ->withoutOverlapping(config('system.commands_overlapping_expires_at'))
            ->appendOutputTo(storage_path('logs/compute_author_segments.log'));

        $schedule->command(ComputeSectionSegments::COMMAND)
            ->dailyAt('03:00')
            ->runInBackground()
            ->withoutOverlapping(config('system.commands_overlapping_expires_at'))
            ->appendOutputTo(storage_path('logs/compute_section_segments.log'));
    }

    /**
     * Pageviews, timespent and session pageviews aggregation and cleanups
     * @param Schedule $schedule
     */
    private function aggregations(Schedule $schedule)
    {
        // Aggregates current 20-minute interval (may not be completed yet)
        $schedule->command(AggregatePageviews::COMMAND, ["--now='+20 minutes'"])
            ->everyMinute()
            ->runInBackground()
            ->withoutOverlapping(config('system.commands_overlapping_expires_at'))
            ->appendOutputTo(storage_path('logs/aggregate_pageviews.log'));

        // Aggregates last 20-minute interval only once
        $schedule->command(AggregatePageviews::COMMAND)
            ->cron('1-59/20 * * * *')
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/aggregate_pageviews.log'));

        $schedule->command(DeleteOldAggregations::COMMAND)
            ->dailyAt('00:10')
            ->runInBackground()
            ->withoutOverlapping(config('system.commands_overlapping_expires_at'))
            ->appendOutputTo(storage_path('logs/delete_old_aggregations.log'));

        $schedule->command(CompressAggregations::COMMAND)
            ->dailyAt('00:20')
            ->runInBackground()
            ->withoutOverlapping(config('system.commands_overlapping_expires_at'))
            ->appendOutputTo(storage_path('logs/compress_aggregations.log'));
    }

    private function dashboard(Schedule $schedule)
    {
        $schedule->command(DashboardRefresh::COMMAND)
            ->everyMinute()
            ->runInBackground()
            ->withoutOverlapping(config('system.commands_overlapping_expires_at'))
            ->appendOutputTo(storage_path('logs/dashboard_refresh.log'));
    }
}
