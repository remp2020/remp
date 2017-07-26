<?php

namespace App\Providers;

use Blade;
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
