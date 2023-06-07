<?php

namespace Remp\BeamModule\Tests\Feature;

use Remp\BeamModule\Console\Commands\CompressSnapshots;
use Remp\BeamModule\Model\ArticleViewsSnapshot;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Remp\BeamModule\Tests\TestCase;

class CompressSnapshotsTest extends TestCase
{
    use RefreshDatabase;

    public function testComputePeriods()
    {
        $command = new CompressSnapshots();
        $periods = $command->computeDayPeriods(Carbon::now(), 0, 60);
        $this->assertCount(1, $periods);

        $periods = $command->computeDayPeriods(Carbon::now(), 0, 60 * 24);
        $this->assertCount(1, $periods);

        $periods = $command->computeDayPeriods(Carbon::now(), 0, 60 * 25);
        $this->assertCount(2, $periods);

        $periods = $command->computeDayPeriods(Carbon::now(), 0, 60 * 60);
        $this->assertCount(3, $periods);
    }

    public function testCompression()
    {
        $start = Carbon::now();
        $start->setTime($start->hour, $start->minute, 0, 0);

         //Last 10 minutes should be kept
        for ($i = 0; $i < 10; $i++) {
            ArticleViewsSnapshot::factory()->create(['time' => (clone $start)->subMinutes($i)]);
        }
        $this->artisan(CompressSnapshots::COMMAND, ['--now' => $start]);
        $this->assertEquals(10, ArticleViewsSnapshot::all()->count());

        // Between
        for ($i = 10; $i < 30; $i++) {
            ArticleViewsSnapshot::factory()->create(['time' => (clone $start)->subMinutes($i)]);
        }

        $this->artisan(CompressSnapshots::COMMAND, ['--now' => $start]);
        $this->assertEquals(10 + 5, ArticleViewsSnapshot::all()->count());
    }
}
