<?php

namespace App\Providers;

use App\RedirectUrlValidator;
use Illuminate\Database\Connection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
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
        if (config('app.force_https')) {
            URL::forceScheme('https');
        }

        Paginator::useBootstrapThree();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(RedirectUrlValidator::class, fn () => RedirectUrlValidator::fromConfig());

        Connection::resolverFor('mysql', function ($connection, $database, $prefix, $config) {
            // Use local resolver to control DateTimeInterface bindings.
            return new MySqlConnection($connection, $database, $prefix, $config);
        });
    }
}
