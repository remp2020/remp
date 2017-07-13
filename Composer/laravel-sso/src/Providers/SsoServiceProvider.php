<?php

namespace Remp\LaravelSso\Providers;

use Remp\LaravelSso\Contracts\SsoContract;
use Remp\LaravelSso\Contracts\Remp\Sso;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class SsoServiceProvider extends ServiceProvider
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
        $this->app->bind(SsoContract::class, function($app){
            $client = new Client([
                'base_uri' => $app['config']->get('services.remp_sso.base_url'),
            ]);
            return new Sso($client);
        });
    }

    public function provides()
    {
        return [Sso::class];
    }
}
