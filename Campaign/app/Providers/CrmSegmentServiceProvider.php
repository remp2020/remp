<?php

namespace App\Providers;

use App\Contracts\Crm\Segment;
use App\Contracts\SegmentContract;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class CrmSegmentServiceProvider extends ServiceProvider
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
        $this->app->bind(SegmentContract::class, function($app){
            $client = new Client([
                'base_uri' => $app['config']->get('services.segment.base_url'),
                'headers' => [
                    'Authorization' => 'Bearer ' . $app['config']->get('services.segment.token'),
                ],
            ]);
            return new Segment($client);
        });
    }

    public function provides()
    {
        return [SegmentContract::class];
    }
}
