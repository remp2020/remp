<?php

namespace App\Providers;

use App\Contracts\SegmentAggregator;
use Blade;
use Carbon\Carbon;
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
        Carbon::setToStringFormat(DATE_RFC3339);

        if (class_exists('Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider')) {
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        }

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
        $this->bindObservers();

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

        Blade::directive('class', function ($expression) {
            return "<?php echo blade_class({$expression}); ?>";
        });
    }

    public function bindObservers()
    {
        \App\Banner::observe(\App\Observers\Banner::class);
        \App\Campaign::observe(\App\Observers\Campaign::class);
        \App\CampaignSegment::observe(\App\Observers\CampaignSegment::class);
        \App\Schedule::observe(\App\Observers\Schedule::class);
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
