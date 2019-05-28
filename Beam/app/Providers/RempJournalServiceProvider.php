<?php

namespace App\Providers;

use App\Console\Commands\AggregateArticlesViews;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Remp\Journal\Journal;
use Remp\Journal\JournalContract;
use Remp\Journal\JournalException;

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
        // Guzzle client with exponential back-off
        $decider = function (
            $retries,
            Request $request,
            Response $response = null,
            RequestException $exception = null
        ) {
            if ($retries >= 11) {
                return false;
            }
            if ($exception instanceof JournalException) {
                return true;
            }
            if ($response) {
                if ($response->getStatusCode() >= 500) {
                    return true; // Retry on server errors
                }
            }
            return false;
        };
        $handlerStack = HandlerStack::create(new CurlHandler());
        $handlerStack->push(Middleware::retry($decider));

        $this->app->when(AggregateArticlesViews::class)
            ->needs(JournalContract::class)
            ->give(function (Application $app) use ($handlerStack) {
                $client = new Client([
                    'base_uri' => $app['config']->get('services.remp.beam.segments_addr'),
                    'handler' => $handlerStack,
                ]);

                $redis = $app->make('redis')->connection()->client();
                return new Journal($client, $redis);
            });

        $this->app->bind(JournalContract::class, function (Application $app) {
            $client = new Client([
                'base_uri' => $app['config']->get('services.remp.beam.segments_addr'),
            ]);
            $redis = $app->make('redis')->connection()->client();
            return new Journal($client, $redis);
        });
    }

    public function provides()
    {
        return [JournalContract::class];
    }
}
