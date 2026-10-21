<?php

namespace Remp\CampaignModule\Contracts\Remp;

use GuzzleHttp\Client;
use Predis\ClientInterface;
use Remp\CampaignModule\Contracts\SegmentContract;
use Remp\CampaignModule\Contracts\SegmentContractFactory;

class SegmentFactory implements SegmentContractFactory
{
    public function config(): array
    {
        return [
            'base_url' => config('services.remp.beam.segments_addr'),
            'timeout' => config('services.remp.beam.segments_timeout'),
        ];
    }

    public function create(array $config, ClientInterface|\Redis $redis): SegmentContract
    {
        return new Segment(new Client([
            'base_uri' => $config['base_url'],
            'timeout' => $config['timeout'],
            'connect_timeout' => 1,
        ]));
    }
}
