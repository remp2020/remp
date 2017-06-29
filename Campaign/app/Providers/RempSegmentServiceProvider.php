<?php

namespace App\Providers;

use App\Contracts\Remp\Segment;
use App\Contracts\SegmentContract;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class RempSegmentServiceProvider extends ServiceProvider
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
                'base_uri' => $app['config']->get('services.remp_segment.base_url'),
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
