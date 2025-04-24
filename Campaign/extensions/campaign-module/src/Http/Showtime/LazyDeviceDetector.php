<?php

namespace Remp\CampaignModule\Http\Showtime;

use DeviceDetector\Cache\PSR16Bridge;
use Psr\SimpleCache\CacheInterface;

class LazyDeviceDetector
{
    private $detector;

    public function __construct(
        private CacheInterface $cache,
    ) {
    }

    public function get($userAgent)
    {
        if (!$this->detector) {
            $this->detector = new \DeviceDetector\DeviceDetector();
            $this->detector->setCache(new Psr16Bridge($this->cache));
        }

        $this->detector->setUserAgent($userAgent);
        $this->detector->parse();
        return $this->detector;
    }
}
