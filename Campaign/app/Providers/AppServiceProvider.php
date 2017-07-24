<?php

namespace App\Providers;

use App\Contracts\SegmentAggregator;
use Blade;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

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

        Blade::directive('json', function ($expression) {
            return "\Psy\Util\Json::encode({$expression})";
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
    }
}
