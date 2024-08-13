<?php

namespace Remp\CampaignModule\Providers;

use Remp\CampaignModule\Contracts\StatsContract;
use GuzzleHttp\Client;
use Remp\CampaignModule\Contracts\Remp\Stats;
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
                'base_uri' => config('services.remp.beam.segments_addr'),
                'timeout' => config('services.remp.beam.segments_timeout'),
                'connect_timeout' => 1,
            ]);

            return new Stats($client);
        });
    }
}
