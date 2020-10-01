<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Snowplow\RefererParser\Config\YamlConfigReader;
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
            $configReader = new YamlConfigReader(__DIR__ . '/../../vendor/snowplow/referer-parser/resources/referers.yml');
            return new Parser($configReader);
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
