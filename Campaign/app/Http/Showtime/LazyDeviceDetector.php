<?php

namespace App\Http\Showtime;

use DeviceDetector\Cache\PSR6Bridge;
use Psr\Cache\CacheItemPoolInterface;

class LazyDeviceDetector
{
    private $detector;

    public function __construct(
        private CacheItemPoolInterface $cachePool,
    ) {
    }

    public function get($userAgent)
    {
        if (!$this->detector) {
            $this->detector = new \DeviceDetector\DeviceDetector();
            $this->detector->setCache(
                new PSR6Bridge(
                    $this->cachePool
                )
            );
        }

        $this->detector->setUserAgent($userAgent);
        $this->detector->parse();
        return $this->detector;
    }
}
