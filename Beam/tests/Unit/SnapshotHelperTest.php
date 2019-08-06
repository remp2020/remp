<?php

namespace Tests\Unit;

use App\Model\ArticleViewsSnapshot;
use App\Model\Snapshots\SnapshotHelpers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SnapshotHelperTest extends TestCase
{
    use RefreshDatabase;

    /** @var SnapshotHelpers */
    private $snapshotHelpers;

    protected function setUp()
    {
        parent::setUp();
        $this->snapshotHelpers = new SnapshotHelpers();
    }

    public function testExcludedTimePoints()
    {
        $start = Carbon::today();

        $shouldBeExcluded = [];
        $shouldBeKept = [];

        // Window 0-4 min
        $shouldBeKept[] = factory(ArticleViewsSnapshot::class)->create(['time' => $start]);
        $shouldBeExcluded[] = factory(ArticleViewsSnapshot::class)->create(['time' => (clone $start)->addMinutes(1)]);
        $shouldBeExcluded[] = factory(ArticleViewsSnapshot::class)->create(['time' => (clone $start)->addMinutes(2)]);
        $shouldBeExcluded[] = factory(ArticleViewsSnapshot::class)->create(['time' => (clone $start)->addMinutes(3)]);
        $shouldBeExcluded[] = factory(ArticleViewsSnapshot::class)->create(['time' => (clone $start)->addMinutes(4)]);

        // Window 5-9 min
        $shouldBeKept[] = factory(ArticleViewsSnapshot::class)->create(['time' => (clone $start)->addMinutes( 6)]);
        $shouldBeExcluded[] = factory(ArticleViewsSnapshot::class)->create(['time' => (clone $start)->addMinutes(7)]);
        $shouldBeExcluded[] = factory(ArticleViewsSnapshot::class)->create(['time' => (clone $start)->addMinutes(8)]);

        // Window 10-14 min
        $shouldBeKept[] = factory(ArticleViewsSnapshot::class)->create(['time' => (clone $start)->addMinutes(10)]);

        // Window 15-19 min
        $shouldBeKept[] = factory(ArticleViewsSnapshot::class)->create(['time' => (clone $start)->addMinutes(16)]);

        $timePoints = $this->snapshotHelpers->timePoints($start, (clone $start)->addMinutes(20), 5);

        foreach ($shouldBeExcluded as $item) {
            $this->assertTrue(in_array($item->time->toIso8601ZuluString(), $timePoints->toExclude));
        }

        foreach ($shouldBeKept as $item) {
            $this->assertFalse(in_array($item->time->toIso8601ZuluString(), $timePoints->toExclude));
        }
    }
}
