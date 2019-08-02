<?php

namespace App\Console;

use App\Console\Commands\AggregateArticlesViews;
use App\Console\Commands\AggregateConversionEvents;
use App\Console\Commands\AggregatePageviewLoadJob;
use App\Console\Commands\AggregatePageviewTimespentJob;
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

        $schedule->command(SnapshotArticlesViews::COMMAND)
            ->everyMinute();

        $schedule->command(SendNewslettersCommand::COMMAND)
            ->everyMinute();

        $schedule->command(AggregatePageviewLoadJob::COMMAND)
             ->everyMinute()
             ->withoutOverlapping();

        $schedule->command(AggregatePageviewTimespentJob::COMMAND)
            ->everyMinute()
            ->withoutOverlapping();

        $schedule->command(ProcessPageviewSessions::COMMAND)
            ->everyFiveMinutes()
            ->withoutOverlapping();

        $schedule->command(DeleteOldAggregations::COMMAND)
            ->dailyAt('00:10')
            ->withoutOverlapping();

        $schedule->command(CompressAggregations::COMMAND)
            ->dailyAt('00:20')
            ->withoutOverlapping();

        $schedule->command(AggregateArticlesViews::COMMAND, ['--skip-temp-aggregation'])
            ->dailyAt('01:00')
            ->withoutOverlapping();

        $schedule->command(ComputeAuthorsSegments::COMMAND)
            ->dailyAt('02:00')
            ->withoutOverlapping();

        // Aggregate any conversion events that weren't aggregated before due to Segments API fail
        // or other unexpected event
        $schedule->command(AggregateConversionEvents::COMMAND)
            ->dailyAt('3:30')
            ->withoutOverlapping();
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
