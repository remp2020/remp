<?php

namespace App\Providers;

use App\Contracts\JournalContract;
use App\Contracts\Remp\Journal;
use App\Contracts\TrackerContract;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class RempJournalServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(JournalContract::class, function($app){
            $client = new Client([
                'base_uri' => $app['config']->get('services.journal.base_url'),
            ]);
            return new Journal($client);
        });
    }

    public function provides()
    {
        return [JournalContract::class];
    }
}
