<?php

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
        $banner = factory(\App\Banner::class)->create([
            'name' => 'DEMO Medium Rectangle Banner',
            'template' => 'medium_rectangle',
            'offset_horizontal' => 10,
            'offset_vertical' => 10,
        ]);
        $banner->mediumRectangleTemplate()->save(
            factory(\App\MediumRectangleTemplate::class)->make()
        );

        /** @var \App\Banner $altBanner */
        $altBanner = factory(\App\Banner::class)->create([
            'name' => 'DEMO Bar Banner',
            'template' => 'bar',
            'offset_horizontal' => 10,
            'offset_vertical' => 10,
        ]);
        $altBanner->barTemplate()->save(
            factory(\App\BarTemplate::class)->make()
        );

        /** @var \App\Campaign $campaign */
        $campaign = factory(\App\Campaign::class)->create();

        $campaign->segments()->save(
            factory(\App\CampaignSegment::class)->make()
        );

        $campaignBanner = factory(\App\CampaignBanner::class)->create([
            'banner_id' => $banner->id,
        ]);

        $altCampaignBanner = factory(\App\CampaignBanner::class)->create([
            'banner_id' => $altBanner->id,
            'weight' => 2,
        ]);

        $controlGroup = factory(\App\CampaignBanner::class)->create([
            'banner_id' => null,
            'weight' => 3,
            'control_group' => 1,
            'proportion' => 0,
        ]);
    }
}
