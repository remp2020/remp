<?php

namespace App\Providers;

use App\Contracts\GoogleAnalytics\GoogleAnalyticsReporting;
use App\Contracts\GoogleAnalytics\GoogleAnalyticsReportingContract;
use Illuminate\Support\ServiceProvider;
use Google_Client;
use Google_Service_AnalyticsReporting;

class GoogleAnalyticsReportingServiceProvider extends ServiceProvider
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
        $this->app->bind(GoogleAnalyticsReportingContract::class, function () {
            $client = new Google_Client();
            $client->setApplicationName(config('google.app_name'));
            $client->setAuthConfig(config('google.service_account_file'));
            $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
            $analytics = new Google_Service_AnalyticsReporting($client);
            return new GoogleAnalyticsReporting($analytics);
        });
    }

    public function provides()
    {
        return [GoogleAnalyticsReportingContract::class];
    }
}
