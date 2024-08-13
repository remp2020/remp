<?php

namespace App\Providers;

use App\Console\MigrateMakeCommand;
use Illuminate\Support\Composer;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->extend('command.migrate.make', function ($app) {
            return new MigrateMakeCommand(
                $this->app->get('migration.creator'),
                $this->app->get(Composer::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
    }
}
