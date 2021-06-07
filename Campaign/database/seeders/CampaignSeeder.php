<?php

namespace Database\Seeders;

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
        /** @var \App\Banner $banner */
        $banner = \App\Banner::factory()->create([
            'name' => 'DEMO Medium Rectangle Banner',
            'template' => 'medium_rectangle',
            'offset_horizontal' => 10,
            'offset_vertical' => 10,
        ]);
        $banner->mediumRectangleTemplate()->save(
            \App\MediumRectangleTemplate::factory()->make()
        );

        /** @var \App\Banner $altBanner */
        $altBanner = \App\Banner::factory()->create([
            'name' => 'DEMO Bar Banner',
            'template' => 'bar',
            'offset_horizontal' => 10,
            'offset_vertical' => 10,
        ]);
        $altBanner->barTemplate()->save(
            \App\BarTemplate::factory()->make()
        );

        /** @var \App\Campaign $campaign */
        $campaign = \App\Campaign::factory()->create();

        $campaign->segments()->save(
            \App\CampaignSegment::factory()->make()
        );

        $campaignBanner = \App\CampaignBanner::factory()->create([
            'banner_id' => $banner->id,
            'campaign_id' => $campaign->id,
        ]);

        $altCampaignBanner = \App\CampaignBanner::factory()->create([
            'banner_id' => $altBanner->id,
            'campaign_id' => $campaign->id,
            'weight' => 2,
        ]);

        $controlGroup = \App\CampaignBanner::factory()->create([
            'banner_id' => null,
            'campaign_id' => $campaign->id,
            'weight' => 3,
            'control_group' => 1,
            'proportion' => 0,
        ]);
    }
}
