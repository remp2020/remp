<?php

namespace App\Providers;

use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;
use Remp\Journal\Journal;
use Remp\Journal\JournalContract;

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
        $this->app->bind(JournalContract::class, function ($app) {
            $client = new Client([
                'base_uri' => $app['config']->get('services.remp.beam.segments_addr'),
            ]);
            return new Journal($client);
        });
    }

    public function provides()
    {
        return [JournalContract::class];
    }
}
