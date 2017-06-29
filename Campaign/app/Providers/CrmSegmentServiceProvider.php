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
        $this->app->bind(Segment::class, function($app){
            $client = new Client([
                'base_uri' => $app['config']->get('services.crm_segment.base_url'),
                'headers' => [
                    'Authorization' => 'Bearer ' . $app['config']->get('services.crm_segment.token'),
                ],
            ]);
            return new Segment($client);
        });
        $this->app->alias(Segment::class, Segment::ALIAS);
        $this->app->tag(Segment::class, 'segments');
    }

    public function provides()
    {
        return [SegmentContract::class];
    }
}
