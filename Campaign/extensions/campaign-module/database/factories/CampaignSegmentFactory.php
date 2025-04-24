<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Remp\CampaignModule\CampaignSegment;

/** @extends Factory<CampaignSegment> */
class CampaignSegmentFactory extends Factory
{
    protected $model = CampaignSegment::class;

    public function definition()
    {
        return [
            'campaign_id' => 1,
            'code' => 'demo_segment',
            'provider' => 'remp_segment',
        ];
    }
}
