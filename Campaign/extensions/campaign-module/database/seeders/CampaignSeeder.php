<?php

namespace Remp\CampaignModule\Database\Seeders;

use Illuminate\Database\Seeder;

class CampaignSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /** @var \Remp\CampaignModule\Banner $banner */
        $banner = \Remp\CampaignModule\Banner::factory()->create([
            'name' => 'DEMO Medium Rectangle Banner',
            'template' => 'medium_rectangle',
            'offset_horizontal' => 10,
            'offset_vertical' => 10,
        ]);
        $banner->mediumRectangleTemplate()->save(
            \Remp\CampaignModule\MediumRectangleTemplate::factory()->make()
        );

        /** @var \Remp\CampaignModule\Banner $altBanner */
        $altBanner = \Remp\CampaignModule\Banner::factory()->create([
            'name' => 'DEMO Bar Banner',
            'template' => 'bar',
            'offset_horizontal' => 10,
            'offset_vertical' => 10,
        ]);
        $altBanner->barTemplate()->save(
            \Remp\CampaignModule\BarTemplate::factory()->make()
        );

        /** @var \Remp\CampaignModule\Campaign $campaign */
        $campaign = \Remp\CampaignModule\Campaign::factory()->create();

        $campaign->segments()->save(
            \Remp\CampaignModule\CampaignSegment::factory()->make()
        );

        $campaignBanner = \Remp\CampaignModule\CampaignBanner::factory()->create([
            'banner_id' => $banner->id,
            'campaign_id' => $campaign->id,
        ]);

        $altCampaignBanner = \Remp\CampaignModule\CampaignBanner::factory()->create([
            'banner_id' => $altBanner->id,
            'campaign_id' => $campaign->id,
            'weight' => 2,
        ]);

        $controlGroup = \Remp\CampaignModule\CampaignBanner::factory()->create([
            'banner_id' => null,
            'campaign_id' => $campaign->id,
            'weight' => 3,
            'control_group' => 1,
            'proportion' => 0,
        ]);
    }
}
