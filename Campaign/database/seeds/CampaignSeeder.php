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
        ]);
        $banner->mediumRectangleTemplate()->save(
            factory(\App\MediumRectangleTemplate::class)->make()
        );

        /** @var \App\Banner $altBanner */
        $altBanner = factory(\App\Banner::class)->create([
            'name' => 'DEMO Bar Banner',
            'template' => 'bar',
        ]);
        $altBanner->barTemplate()->save(
            factory(\App\BarTemplate::class)->make()
        );

        /** @var \App\Campaign $campaign */
        $campaign = factory(\App\Campaign::class)->create();
        $campaign->fill([
            'banner_id' => $banner->id,
            'alt_banner_id' => $altBanner->id,
        ]);

        $campaign->segments()->save(
            factory(\App\CampaignSegment::class)->make()
        );
    }
}
