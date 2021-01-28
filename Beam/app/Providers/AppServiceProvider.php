<?php

namespace App\Providers;

use App\Http\Resources\SearchResource;
use App\Model\Property\SelectedProperty;
use App\Model\Config\ConversionRateConfig;
use Illuminate\Database\Connection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
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
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ConversionRateConfig::class);

        Connection::resolverFor('mysql', function ($connection, $database, $prefix, $config) {
            // Use local resolver to control DateTimeInterface bindings.
            return new MySqlConnection($connection, $database, $prefix, $config);
        });
    }
}
