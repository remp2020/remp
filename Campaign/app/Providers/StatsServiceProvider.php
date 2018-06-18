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
                'base_uri' => $app['config']->get('services.remp.beam.segments_addr'),
                'timeout' => 1,
                'connect_timeout' => 1,
            ]);

            $offsetInSeconds = (new \DateTimeZone(config("app.timezone")))->getOffset(new \DateTime);

            return new Stats($client, $offsetInSeconds . "s");
        });
    }
}
