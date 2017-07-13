<?php

namespace Remp\LaravelSso\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Remp\LaravelSso\Contracts\Jwt\Guard;

class AuthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Auth::extend('jwt', function ($app, $name, array $config) {
            return $app->make(Guard::class);
        });
    }
}
