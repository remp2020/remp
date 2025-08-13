<?php

namespace App\Providers;

use App\Console\MigrateMakeCommand;
use App\Console\ProcessGenderBalanceCommand;
use App\Console\UploadPageviewsToGorse;
use App\Observers\GenderBalanceObserver;
use Illuminate\Database\Connection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Composer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Remp\BeamModule\Http\Resources\SearchResource;
use Remp\BeamModule\Model\Article;
use Remp\BeamModule\Model\Config\ConversionRateConfig;
use Remp\BeamModule\Model\Property\SelectedProperty;
use Remp\LaravelHelpers\Database\MySqlConnection;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (class_exists('Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider')) {
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
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

        $this->commands([
            ProcessGenderBalanceCommand::class,
            UploadPageviewsToGorse::class,
        ]);

        if (config('internal.gender_balance_enabled')) {
            Article::observe(GenderBalanceObserver::class);
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

        $this->app->extend('command.migrate.make', function ($app) {
            return new MigrateMakeCommand(
                $this->app->get('migration.creator'),
                $this->app->get(Composer::class)
            );
        });
    }
}
