<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Remp\CampaignModule\CampaignBanner;

/** @extends Factory<CampaignBanner> */
class CampaignBannerFactory extends Factory
{
    protected $model = CampaignBanner::class;

    public function definition()
    {
        return [
            'control_group' => 0,
            'proportion' => 100,
            'weight' => 1,
            'uuid' => $this->faker->uuid,
        ];
    }
}
