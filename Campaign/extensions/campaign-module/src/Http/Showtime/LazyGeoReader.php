<?php

namespace Remp\CampaignModule\Http\Showtime;

use GeoIp2\Database\Reader;

class LazyGeoReader
{
    private $reader;

    private $maxmindDatabasePath;

    public function __construct($maxmindDatabasePath)
    {
        $this->maxmindDatabasePath = $maxmindDatabasePath;
    }

    private function get()
    {
        if (!$this->reader) {
            $this->reader = new Reader(realpath($this->maxmindDatabasePath));
        }
        return $this->reader;
    }

    /**
     * @param $ip
     *
     * @return string|null
     * @throws \GeoIp2\Exception\AddressNotFoundException
     * @throws \MaxMind\Db\Reader\InvalidDatabaseException
     */
    public function countryCode($ip): ?string
    {
        $record = $this->get()->country($ip);
        return $record->country->isoCode;
    }
}
