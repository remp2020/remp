<?php

namespace Remp\Widgets\Providers;

use Illuminate\Support\ServiceProvider;

class WidgetServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $path = realpath(__DIR__.'/../../config/laravel-widgets.php');
        $config = $this->app['config']->get('laravel-widgets', []);
        $this->app['config']->set('laravel-widgets', array_merge(require $path, $config));
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'widgets');
    }
}
