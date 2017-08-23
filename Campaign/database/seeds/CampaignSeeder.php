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
        $banner = factory(\App\Banner::class)->create();

        /** @var \App\Campaign $campaign */
        $campaign = factory(\App\Campaign::class)->make();
        $banner->campaigns()->save($campaign);

        $campaign->segments()->save(
            factory(\App\CampaignSegment::class)->make()
        );
    }
}
