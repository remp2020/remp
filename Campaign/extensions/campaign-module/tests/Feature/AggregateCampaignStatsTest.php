<?php

namespace Remp\CampaignModule\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery;
use Remp\CampaignModule\Banner;
use Remp\CampaignModule\Campaign;
use Remp\CampaignModule\CampaignBanner;
use Remp\CampaignModule\CampaignBannerStats;
use Remp\CampaignModule\Console\Commands\AggregateCampaignStats;
use Remp\CampaignModule\Contracts\StatsException;
use Remp\CampaignModule\Contracts\StatsHelper;
use Remp\CampaignModule\Tests\TestCase;

class AggregateCampaignStatsTest extends TestCase
{
    use RefreshDatabase;

    public function testCommandSkipsFailingBannerAndExitsZero()
    {
        $campaign = Campaign::factory()->create();
        $failingBanner = CampaignBanner::factory()->create([
            'campaign_id' => $campaign->id,
            'banner_id' => Banner::factory()->create()->id,
        ]);
        $okBanner = CampaignBanner::factory()->create([
            'campaign_id' => $campaign->id,
            'banner_id' => Banner::factory()->create()->id,
        ]);

        $okStats = [
            'click_count' => (object) ['count' => 3],
            'show_count' => (object) ['count' => 10],
            'payment_count' => (object) ['count' => 0],
            'purchase_count' => (object) ['count' => 0],
            'purchase_sum' => [],
        ];

        $statsHelper = Mockery::mock(StatsHelper::class);
        $statsHelper->shouldReceive('variantStats')
            ->with(Mockery::on(fn (CampaignBanner $banner) => $banner->id === $failingBanner->id), Mockery::any(), Mockery::any())
            ->andThrow(new StatsException('cURL error 28: SSL connection timeout'));
        $statsHelper->shouldReceive('variantStats')
            ->with(Mockery::on(fn (CampaignBanner $banner) => $banner->id === $okBanner->id), Mockery::any(), Mockery::any())
            ->andReturn($okStats);

        $this->app->instance(StatsHelper::class, $statsHelper);

        $now = Carbon::now();

        $this->artisan(AggregateCampaignStats::COMMAND, [
            '--now' => $now->toDateTimeString(),
            '--include-inactive' => true,
        ])->assertExitCode(0);

        $timeFrom = (clone $now)->minute(0)->second(0);
        $timeTo = (clone $timeFrom)->addHour();

        $this->assertDatabaseMissing('campaign_banner_stats', [
            'campaign_banner_id' => $failingBanner->id,
            'time_from' => $timeFrom,
            'time_to' => $timeTo,
        ]);

        $okStatsRow = CampaignBannerStats::where([
            'campaign_banner_id' => $okBanner->id,
            'time_from' => $timeFrom,
            'time_to' => $timeTo,
        ])->first();

        $this->assertNotNull($okStatsRow);
        $this->assertEquals(3, $okStatsRow->click_count);
        $this->assertEquals(10, $okStatsRow->show_count);
    }

    public function testCommandFailsLoudlyWhenEveryBannerFails()
    {
        $this->expectException(StatsException::class);

        $campaign = Campaign::factory()->create();
        CampaignBanner::factory()->create([
            'campaign_id' => $campaign->id,
            'banner_id' => Banner::factory()->create()->id,
        ]);
        CampaignBanner::factory()->create([
            'campaign_id' => $campaign->id,
            'banner_id' => Banner::factory()->create()->id,
        ]);

        $statsHelper = Mockery::mock(StatsHelper::class);
        $statsHelper->shouldReceive('variantStats')
            ->andThrow(new StatsException('cURL error 28: SSL connection timeout'));

        $this->app->instance(StatsHelper::class, $statsHelper);

        $this->artisan(AggregateCampaignStats::COMMAND, [
            '--now' => Carbon::now()->toDateTimeString(),
            '--include-inactive' => true,
        ])->assertExitCode(1);
    }
}
