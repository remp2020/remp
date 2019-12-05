<?php

namespace App\Providers;

use App\Model\Property\SelectedProperty;
use App\Model\Config\ConversionRateConfig;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
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
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ConversionRateConfig::class);
    }
}
