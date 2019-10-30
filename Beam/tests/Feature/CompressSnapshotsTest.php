<?php

namespace Tests\Feature;

use App\Console\Commands\CompressSnapshots;
use App\Model\ArticleViewsSnapshot;
use Carbon\Carbon;
use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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
        $start = Carbon::now()->second(0);

         //Last 10 minutes should be kept
        for ($i = 0; $i < 10; $i++) {
            factory(ArticleViewsSnapshot::class)->create(['time' => (clone $start)->subMinutes($i)]);
        }
        $this->artisan(CompressSnapshots::COMMAND, ['--now' => $start]);
        $this->assertEquals(10, ArticleViewsSnapshot::all()->count());

        // Between
        for ($i = 10; $i < 30; $i++) {
            factory(ArticleViewsSnapshot::class)->create(['time' => (clone $start)->subMinutes($i)]);
        }

        $this->artisan(CompressSnapshots::COMMAND, ['--now' => $start]);
        $this->assertEquals(10 + 5, ArticleViewsSnapshot::all()->count());
    }
}
