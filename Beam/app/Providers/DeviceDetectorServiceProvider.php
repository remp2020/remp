<?php

namespace App\Providers;

use DeviceDetector\Cache\PSR6Bridge;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use DeviceDetector\DeviceDetector;

class DeviceDetectorServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->bind(DeviceDetector::class, function ($app) {
            $dd = new DeviceDetector();

            // 'symfony/cache' dependency provides Psr16Adapter cache adapter
            $cache = app('cache.psr6');
            $dd->setCache(new PSR6Bridge($cache));

            return $dd;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [DeviceDetector::class];
    }
}
