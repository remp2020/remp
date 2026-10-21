<?php

namespace Remp\CampaignModule\Contracts\Crm;

use GuzzleHttp\Client;
use Predis\ClientInterface;
use Remp\CampaignModule\Contracts\SegmentContract;
use Remp\CampaignModule\Contracts\SegmentContractFactory;

class SegmentFactory implements SegmentContractFactory
{
    public function config(): array
    {
        return [
            'base_url' => config('services.remp.crm_segment.base_url'),
            'token' => config('services.remp.crm_segment.token'),
        ];
    }

    public function create(array $config, ClientInterface|\Redis $redis): SegmentContract
    {
        return new Segment(new Client([
            'base_uri' => $config['base_url'],
            'headers' => [
                'Authorization' => 'Bearer ' . $config['token'],
            ],
        ]), $redis);
    }
}
