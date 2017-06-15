<?php

namespace App\Providers;

use Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->bind('App\Models\Dimension\Map', function ($app) {
            return new \App\Models\Dimension\Map(config('banners.dimensions'));
        });
        $this->app->bind('App\Models\Position\Map', function ($app) {
            return new \App\Models\Position\Map(config('banners.positions'));
        });
        $this->app->bind('App\Models\Alignment\Map', function ($app) {
            return new \App\Models\Alignment\Map(config('banners.alignments'));
        });

        Blade::directive('yesno', function ($expression) {
            return "{$expression} ? 'Yes' : 'No'";
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment() == 'local') {
            $this->app->register('Barryvdh\Debugbar\ServiceProvider');
        }
    }
}
