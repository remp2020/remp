<?php

namespace Remp\CampaignModule;

use Remp\CampaignModule\Console\Commands\AggregateCampaignStats;
use Remp\CampaignModule\Console\Commands\CampaignsRefreshCache;
use Remp\CampaignModule\Contracts\SegmentAggregator;
use Remp\CampaignModule\Http\Middleware\CollectionQueryString;
use Remp\CampaignModule\Http\Resources\SearchResource;
use Remp\CampaignModule\Http\Showtime\LazyDeviceDetector;
use Remp\CampaignModule\Http\Showtime\LazyGeoReader;
use Remp\CampaignModule\Http\Showtime\ShowtimeConfig;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Connection;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Pagination\Paginator;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Predis\ClientInterface;
use Remp\LaravelHelpers\Database\MySqlConnection;
use Remp\LaravelSso\Http\Middleware\VerifyJwtToken;

class CampaignServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(Filesystem $filesystem)
    {
        $this->bindObservers();

        Paginator::useBootstrapThree();

        SearchResource::withoutWrapping();

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        Route::group([
            'prefix' => 'api',
            'middleware' => 'api',
        ], function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        });

        Route::group([
            'middleware' => 'web'
        ], function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        });

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'campaign');

        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'campaign');

        $this->publishes([
            __DIR__ . '/../public/' => public_path('vendor/campaign'),
            __DIR__ . '/../config/banners.php' => config_path('banners.php'),
            __DIR__ . '/../config/services.php' => config_path('services.remp.php'),
            __DIR__ . '/../config/newsletter_banners.php' => config_path('newsletter_banners.php'),
            __DIR__ . '/../config/search.php' => config_path('search.php'),
        ], ['campaign-assets', 'laravel-assets']);

        $this->registerCommands();

        /** @var Router $router */
        $router = $this->app['router'];

        $router->aliasMiddleware('auth.jwt', VerifyJwtToken::class);
        $router->aliasMiddleware('collectionQueryString', CollectionQueryString::class);

        if ($this->app->runningInConsole()) {
            $this->app->booted(function () {
                $schedule = $this->app->make(Schedule::class);
                (new Scheduler())->schedule($schedule);
            });
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Connection::resolverFor('mysql', function ($connection, $database, $prefix, $config) {
            // Use local resolver to control DateTimeInterface bindings.
            return new MySqlConnection($connection, $database, $prefix, $config);
        });

        $dimensionMap = new \Remp\CampaignModule\Models\Dimension\Map(config('banners.dimensions', []));
        $positionsMap = new \Remp\CampaignModule\Models\Position\Map(config('banners.positions', []));
        $alignmentsMap = new \Remp\CampaignModule\Models\Alignment\Map(config('banners.alignments', []));
        $colorSchemesMap = new \Remp\CampaignModule\Models\ColorScheme\Map(config('banners.color_schemes', []));

        $this->app->bind(\Remp\CampaignModule\Models\Dimension\Map::class, function () use ($dimensionMap) {
            return $dimensionMap;
        });
        $this->app->bind(\Remp\CampaignModule\Models\Position\Map::class, function () use ($positionsMap) {
            return $positionsMap;
        });
        $this->app->bind(\Remp\CampaignModule\Models\Alignment\Map::class, function () use ($alignmentsMap) {
            return $alignmentsMap;
        });
        $this->app->bind(\Remp\CampaignModule\Models\ColorScheme\Map::class, function () use ($colorSchemesMap) {
            return $colorSchemesMap;
        });
        $this->app->bind(ClientInterface::class, function () {
            return Redis::connection()->client();
        });
        $this->app->bind(LazyGeoReader::class, function () {
            return new LazyGeoReader(config("services.remp.maxmind.database"));
        });

        $this->app->bind(ShowtimeConfig::class, function () {
            return (new ShowtimeConfig(
                debugKey: config('banners.campaign_debug_key'),
                prioritizeBannerOnSamePosition: config('banners.prioritize_banners_on_same_position'),
                oneTimeBannerEnabled: config("banners.one_time_banner_enabled"),
            ));
        });

        $this->app->bind(SegmentAggregator::class, function (Application $app) {
            return new SegmentAggregator($app->tagged(SegmentAggregator::TAG));
        });

        $this->app->bind(LazyDeviceDetector::class, function () {
            if (config("database.redis.client") === 'phpredis') {
                $cachePool = new \Cache\Adapter\Redis\RedisCachePool(Redis::connection()->client());
            } else {
                $cachePool = new \Cache\Adapter\Predis\PredisCachePool(Redis::connection()->client());
            }

            return (new LazyDeviceDetector($cachePool));
        });
    }

    protected function registerCommands(): void
    {
        $this->commands([
            AggregateCampaignStats::class,
            CampaignsRefreshCache::class
        ]);
    }

    public function bindObservers()
    {
        \Remp\CampaignModule\Banner::observe(\Remp\CampaignModule\Observers\Banner::class);
        \Remp\CampaignModule\Campaign::observe(\Remp\CampaignModule\Observers\Campaign::class);
        \Remp\CampaignModule\CampaignBanner::observe(\Remp\CampaignModule\Observers\CampaignBanner::class);
        \Remp\CampaignModule\CampaignSegment::observe(\Remp\CampaignModule\Observers\CampaignSegment::class);
        \Remp\CampaignModule\Schedule::observe(\Remp\CampaignModule\Observers\Schedule::class);
        \Remp\CampaignModule\Snippet::observe(\Remp\CampaignModule\Observers\Snippet::class);
    }
}
