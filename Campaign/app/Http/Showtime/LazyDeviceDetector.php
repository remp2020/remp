<?php

namespace App\Http\Showtime;

use DeviceDetector\Cache\PSR6Bridge;
use Predis\ClientInterface;

class LazyDeviceDetector
{
    private $detector;

    private $redis;

    public function __construct(ClientInterface $redis)
    {
        $this->redis = $redis;
    }

    public function get($userAgent)
    {
        if (!$this->detector) {
            $this->detector = new \DeviceDetector\DeviceDetector();
            $this->detector->setCache(
                new PSR6Bridge(
                    new \Cache\Adapter\Predis\PredisCachePool($this->redis)
                )
            );
        }

        $this->detector->setUserAgent($userAgent);
        $this->detector->parse();
        return $this->detector;
    }
}
