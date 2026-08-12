<?php

namespace Remp\CampaignModule\Providers;

use Remp\CampaignModule\Contracts\StatsContract;
use GuzzleHttp\Client;
use Remp\CampaignModule\Contracts\Remp\Stats;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class StatsServiceProvider extends ServiceProvider
{
    private const CONNECT_TIMEOUT = 3;

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
                'timeout' => config('services.remp.beam.segments_timeout') + self::CONNECT_TIMEOUT,
                'connect_timeout' => self::CONNECT_TIMEOUT,
            ]);

            return new Stats($client);
        });
    }
}
