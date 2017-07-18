<?php

namespace App\Providers;

use App\Contracts\SegmentAggregator;
use Blade;
use GuzzleHttp\Client;
use Illuminate\Foundation\Application;
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
        $this->app->bind(\App\Models\Dimension\Map::class, function ($app) {
            return new \App\Models\Dimension\Map(config('banners.dimensions'));
        });
        $this->app->bind(\App\Models\Position\Map::class, function ($app) {
            return new \App\Models\Position\Map(config('banners.positions'));
        });
        $this->app->bind(\App\Models\Alignment\Map::class, function ($app) {
            return new \App\Models\Alignment\Map(config('banners.alignments'));
        });

        $this->bindBladeDirectives();

        $this->app->bind(SegmentAggregator::class, function (Application $app) {
            return new SegmentAggregator($app->tagged(SegmentAggregator::TAG));
        });
    }

    public function bindBladeDirectives()
    {
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
