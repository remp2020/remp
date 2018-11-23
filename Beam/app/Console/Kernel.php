<?php

namespace App\Console;

use App\Console\Commands\AggregateArticlesViews;
use App\Console\Commands\AggregatePageviewLoadJob;
use App\Console\Commands\AggregatePageviewTimespentJob;
use App\Console\Commands\SendNewslettersCommand;
use App\Console\Commands\CompressAggregations;
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

        $schedule->command(SendNewslettersCommand::COMMAND)
            ->everyMinute();

        $schedule->command(AggregatePageviewLoadJob::COMMAND)
             ->hourlyAt(5)
             ->withoutOverlapping();

        $schedule->command(AggregatePageviewTimespentJob::COMMAND)
            ->hourlyAt(5)
            ->withoutOverlapping();

        // TODO: temporarily turned off to figure out what is a cause of "MySQL connection refused"
        //$schedule->command(AggregateArticlesViews::COMMAND)
        //    ->dailyAt('00:10')
        //    ->withoutOverlapping();

        $schedule->command(CompressAggregations::COMMAND)
            ->dailyAt('00:10')
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
