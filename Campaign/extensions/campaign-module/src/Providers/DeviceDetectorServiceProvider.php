<?php

namespace Remp\CampaignModule\Providers;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use DeviceDetector\Cache\PSR6Bridge;
use DeviceDetector\DeviceDetector;
use Illuminate\Support\Facades\Redis;

class DeviceDetectorServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->bind(DeviceDetector::class, function ($app) {
            if (config("database.redis.client") === 'phpredis') {
                $cachePool = new \Cache\Adapter\Redis\RedisCachePool(Redis::connection()->client());
            } else {
                $cachePool = new \Cache\Adapter\Predis\PredisCachePool(Redis::connection()->client());
            }
            $dd = new DeviceDetector();
            $dd->setCache(
                new PSR6Bridge(
                    $cachePool
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
