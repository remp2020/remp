<?php

namespace Remp\CampaignModule\Contracts\Crm;

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
        return new Segment(
            baseUrl: $config['base_url'],
            token: $config['token'],
            redis: $redis,
        );
    }
}
