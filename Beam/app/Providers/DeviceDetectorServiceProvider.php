<?php

namespace App\Providers;

use Illuminate\Contracts\Support\DeferrableProvider;
use Madewithlove\IlluminatePsrCacheBridge\Laravel\CacheItemPool;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\ServiceProvider;
use DeviceDetector\Cache\PSR6Bridge;
use DeviceDetector\DeviceDetector;

class DeviceDetectorServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->bind(DeviceDetector::class, function ($app) {
            $dd = new DeviceDetector();
            $dd->setCache(
                new PSR6Bridge(
                    new CacheItemPool($app->make(Repository::class))
                )
            );

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
