<?php

namespace App\Providers;

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
        $this->app->bind(Stats::class, function (Application $app) {
            $client = new Client([
                'base_uri' => $app['config']->get('services.remp.beam.stats_addr'),
                'timeout' => 1,
                'connect_timeout' => 1,
            ]);
            return new Stats($client, 0);
        });
    }
}
