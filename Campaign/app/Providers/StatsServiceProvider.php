<?php

namespace App\Providers;

use App\Contracts\StatsContract;
use GuzzleHttp\Client;
use App\Contracts\Remp\Stats;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class StatsServiceProvider extends ServiceProvider
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
        $this->app->bind(StatsContract::class, function (Application $app) {
            $client = new Client([
                'base_uri' => $app['config']->get('services.remp.beam.segments_addr'),
                'timeout' => 1,
                'connect_timeout' => 1,
            ]);

            return new Stats($client);
        });
    }
}
