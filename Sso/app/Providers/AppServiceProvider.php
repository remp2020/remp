<?php

namespace App\Providers;

use Illuminate\Database\Connection;
use Illuminate\Pagination\Paginator;
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
    }
}
