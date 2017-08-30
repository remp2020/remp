<?php

namespace App\Providers;

use App\Contracts\Remp\Tracker;
use App\Contracts\TrackerContract;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class RempTrackerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(TrackerContract::class, function ($app) {
            $client = new Client([
                'base_uri' => $app['config']->get('services.remp.beam.tracker_addr'),
            ]);
            return new Tracker($client);
        });
    }

    public function provides()
    {
        return [TrackerContract::class];
    }
}
