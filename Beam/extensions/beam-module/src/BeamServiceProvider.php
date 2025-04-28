<?php

namespace Remp\BeamModule;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Connection;
use Illuminate\Foundation\Application;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Remp\BeamModule\Console\Commands\AggregateArticlesViews;
use Remp\BeamModule\Contracts\Mailer\Mailer;
use Remp\BeamModule\Contracts\Mailer\MailerContract;
use Remp\BeamModule\Http\Controllers\JournalProxyController;
use Remp\BeamModule\Http\Resources\SearchResource;
use Remp\BeamModule\Model\Config\ConversionRateConfig;
use Remp\BeamModule\Model\Property\SelectedProperty;
use Remp\Journal\DummyTokenProvider;
use Remp\Journal\Journal;
use Remp\Journal\JournalContract;
use Remp\Journal\JournalException;
use Remp\Journal\TokenProvider;
use Remp\LaravelHelpers\Database\MySqlConnection;

class BeamServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (config('app.force_https')) {
            URL::forceScheme('https');
        }

        Paginator::useBootstrapThree();

        // Global selector of current property token
        if (!config('beam.disable_token_filtering')) {
            View::composer('*', function ($view) {
                $selectedProperty = resolve(SelectedProperty::class);
                $view->with('accountPropertyTokens', $selectedProperty->selectInputData());
            });
        }

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

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'beam');

        $this->registerCommands();

        if ($this->app->runningInConsole()) {
            $publishPaths = [
                __DIR__ . '/../public/' => public_path('vendor/beam'),
                __DIR__ .'/../config/beam.php' => config_path('beam.php'),
                __DIR__ .'/../config/services.php' => config_path('services.remp.php'),
                __DIR__ .'/../config/system.php' => config_path('system.php'),
            ];

            // MySqlSchemaState::load uses hard-coded "mysql" command as well, this might change in the future because
            // of the mariadb executable moving away.
            $checkCommand = (PHP_OS_FAMILY === 'Windows') ? 'where' : 'which';
            $mysqlClientCheck = Process::run("$checkCommand mysql");
            if ($mysqlClientCheck->successful()) {
                $publishPaths[__DIR__ .'/../database/schema/mysql-schema.sql'] = database_path('schema/mysql-schema.sql');
            }

            $this->publishes($publishPaths, ['beam-assets', 'laravel-assets']);

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
        /**
         * @deprecated Use static `ConversionRateConfig::build()` method instead of resolving from container.
         */
        $this->app->singleton(ConversionRateConfig::class);

        Connection::resolverFor('mysql', function ($connection, $database, $prefix, $config) {
            // Use local resolver to control DateTimeInterface bindings.
            return new MySqlConnection($connection, $database, $prefix, $config);
        });


        // RempJournal
        $decider = function (
            $retries,
            Request $request,
            Response $response = null,
            RequestException $exception = null
        ) {
            if ($retries >= 11) {
                return false;
            }
            if ($exception instanceof JournalException) {
                return true;
            }
            if ($response) {
                if ($response->getStatusCode() >= 500) {
                    return true; // Retry on server errors
                }
            }
            return false;
        };
        $handlerStack = HandlerStack::create(new CurlHandler());
        $handlerStack->push(Middleware::retry($decider));

        if (config('beam.disable_token_filtering')) {
            $this->app->bind(TokenProvider::class, DummyTokenProvider::class);
        } else {
            $this->app->bind(TokenProvider::class, SelectedProperty::class);
        }

        $tokenProvider = $this->app->make(TokenProvider::class);

        $this->app->when(AggregateArticlesViews::class)
            ->needs(JournalContract::class)
            ->give(function (Application $app) use ($handlerStack, $tokenProvider) {
                $client = new Client([
                    'base_uri' => $app['config']->get('services.remp.beam.segments_addr'),
                    'handler' => $handlerStack,
                ]);

                $redis = $app->make('redis')->connection()->client();
                return new Journal($client, $redis, $tokenProvider);
            });

        $this->app->bind(JournalContract::class, function (Application $app) use ($tokenProvider) {
            $client = new Client([
                'base_uri' => $app['config']->get('services.remp.beam.segments_addr'),
            ]);
            $redis = $app->make('redis')->connection()->client();
            return new Journal($client, $redis, $tokenProvider);
        });

        $this->app->when(JournalProxyController::class)
            ->needs(Client::class)
            ->give(function (Application $app) {
                $client = new Client([
                    'base_uri' => $app['config']->get('services.remp.beam.segments_addr'),
                ]);

                return $client;
            });

        // RempMailer
        $this->app->bind(MailerContract::class, function ($app) {
            $client = new Client([
                'base_uri' => $app['config']->get('services.remp.mailer.web_addr'),
                'headers' => [
                    'Authorization' => 'Bearer ' . $app['config']->get('services.remp.mailer.api_token'),
                ],
            ]);
            return new Mailer($client);
        });
    }

    protected function registerCommands(): void
    {
        $this->commands([
            Console\Commands\AggregateArticlesViews::class,
            Console\Commands\AggregateConversionEvents::class,
            Console\Commands\AggregatePageviewLoadJob::class,
            Console\Commands\AggregatePageviews::class,
            Console\Commands\AggregatePageviewTimespentJob::class,
            Console\Commands\CompressAggregations::class,
            Console\Commands\CompressSnapshots::class,
            Console\Commands\ComputeAuthorsSegments::class,
            Console\Commands\ComputeSectionSegments::class,
            Console\Commands\DashboardRefresh::class,
            Console\Commands\DeleteDuplicatePageviews::class,
            Console\Commands\DeleteOldAggregations::class,
            Console\Commands\ElasticDataRetention::class,
            Console\Commands\ElasticWriteAliasRollover::class,
            Console\Commands\PostInstallCommand::class,
            Console\Commands\ProcessConversionSources::class,
            Console\Commands\ProcessPageviewLoyalVisitors::class,
            Console\Commands\SendNewslettersCommand::class,
            Console\Commands\SnapshotArticlesViews::class,
        ]);
    }
}
