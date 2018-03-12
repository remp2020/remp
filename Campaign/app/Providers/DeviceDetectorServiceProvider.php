<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use DeviceDetector\DeviceDetector;
use DeviceDetector\Parser\Device\Mobile;

class DeviceDetectorServiceProvider extends ServiceProvider
{
    public function provides()
    {
        return [DeviceDetector::class, Mobile::class];
    }
}
