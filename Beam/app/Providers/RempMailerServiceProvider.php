<?php

namespace App\Providers;

use App\Contracts\Mailer\Mailer;
use App\Contracts\Mailer\MailerContract;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class RempMailerServiceProvider extends ServiceProvider
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
        $this->app->bind(MailerContract::class, function ($app) {
            $client = new Client([
                'base_uri' => $app['config']->get('services.remp.mailer.web_addr'),
                'headers' => [
                    'Authorization' => 'Bearer ' . $app['config']->get('services.remp.mailer.api_token'),
                ],
            ]);
            return new Mailer($client);
        });
    }

    public function provides()
    {
        return [MailerContract::class];
    }
}
