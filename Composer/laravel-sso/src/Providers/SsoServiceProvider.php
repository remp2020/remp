<?php

namespace Remp\LaravelSso\Providers;

use Remp\LaravelSso\Contracts\Jwt\Guard as JWTGuard;
use Remp\LaravelSso\Contracts\Token\Guard as TokenGuard;
use Remp\LaravelSso\Contracts\SsoContract;
use Remp\LaravelSso\Contracts\Remp\Sso;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;

class SsoServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $path = realpath(__DIR__.'/../../config/services.php');
        $config = $this->app['config']->get('services', []);
        $this->app['config']->set('services', array_merge(require $path, $config));

        Auth::extend('jwt', function ($app, $name, array $config) {
            return $app->make(JWTGuard::class);
        });
        // this is to resolve jwt auth conflict in SSO in a non-breaking fasion
        Auth::extend('jwtx', function ($app, $name, array $config) {
            return $app->make(JWTGuard::class);
        });
        Auth::extend('token', function($app, $name, array $config) {
            return $app->make(TokenGuard::class);
        });
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
                'base_uri' => $app['config']->get('services.remp.sso.web_addr'),
            ]);
            return new Sso($client);
        });
    }

    public function provides()
    {
        return [Sso::class];
    }
}
