<?php

namespace Remp\CampaignModule\Providers;

use Remp\CampaignModule\Contracts\SegmentAggregator;
use Remp\CampaignModule\Http\Showtime\DeviceRulesEvaluator;
use Remp\CampaignModule\Http\Showtime\LazyDeviceDetector;
use Remp\CampaignModule\Http\Showtime\LazyGeoReader;
use Remp\CampaignModule\Http\Showtime\Showtime;
use Remp\CampaignModule\Http\Showtime\ShowtimeConfig;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

class ShowtimeServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(Showtime::class, function ($app) {
            return new Showtime(
                Redis::connection()->client(),
                $this->app->get(SegmentAggregator::class),
                $this->app->get(LazyGeoReader::class),
                $this->app->get(ShowtimeConfig::class),
                $this->app->get(DeviceRulesEvaluator::class),
                $this->app->get(LoggerInterface::class),
            );
        });

        $this->app->bind(DeviceRulesEvaluator::class, function ($app) {
            return new DeviceRulesEvaluator(
                Redis::connection()->client(),
                $this->app->get(LazyDeviceDetector::class),
            );
        });
    }

    public function provides()
    {
        return [Showtime::class];
    }
}
