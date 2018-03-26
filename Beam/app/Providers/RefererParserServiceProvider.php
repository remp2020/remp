<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Snowplow\RefererParser\Parser;

class RefererParserServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    public function register()
    {
        $this->app->bind(Parser::class, function ($app) {
            $parser = new Parser();
            return $parser;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [Parser::class];
    }
}
