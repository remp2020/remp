<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Widget;

class WidgetServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Widget::group('article.show.info')->addWidget('\App\Widgets\GenderBalanceWidget');
    }
}
