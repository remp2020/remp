<?php

namespace Remp\BeamModule\Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Remp\BeamModule\Model\ArticleViewsSnapshot;
use Remp\BeamModule\Model\Snapshots\SnapshotHelpers;
use Remp\BeamModule\Tests\TestCase;

class SnapshotHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @var SnapshotHelpers */
    private $snapshotHelpers;

    protected function setUp(): void
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
        $shouldBeKept[] = ArticleViewsSnapshot::factory()->create(['time' => $start]);
        $shouldBeExcluded[] = ArticleViewsSnapshot::factory()->create(['time' => (clone $start)->addMinutes(1)]);
        $shouldBeExcluded[] = ArticleViewsSnapshot::factory()->create(['time' => (clone $start)->addMinutes(2)]);
        $shouldBeExcluded[] = ArticleViewsSnapshot::factory()->create(['time' => (clone $start)->addMinutes(3)]);
        $shouldBeExcluded[] = ArticleViewsSnapshot::factory()->create(['time' => (clone $start)->addMinutes(4)]);

        // Window 5-9 min
        $shouldBeKept[] = ArticleViewsSnapshot::factory()->create(['time' => (clone $start)->addMinutes(6)]);
        $shouldBeExcluded[] = ArticleViewsSnapshot::factory()->create(['time' => (clone $start)->addMinutes(7)]);
        $shouldBeExcluded[] = ArticleViewsSnapshot::factory()->create(['time' => (clone $start)->addMinutes(8)]);

        // Window 10-14 min
        $shouldBeKept[] = ArticleViewsSnapshot::factory()->create(['time' => (clone $start)->addMinutes(10)]);

        // Window 15-19 min
        $shouldBeKept[] = ArticleViewsSnapshot::factory()->create(['time' => (clone $start)->addMinutes(16)]);

        $timePoints = $this->snapshotHelpers->timePoints($start, (clone $start)->addMinutes(20), 5);

        foreach ($shouldBeExcluded as $item) {
            $this->assertTrue(in_array($item->time->toIso8601ZuluString(), $timePoints->getExcludedPoints()));
        }

        foreach ($shouldBeKept as $item) {
            $this->assertFalse(in_array($item->time->toIso8601ZuluString(), $timePoints->getExcludedPoints()));
        }
    }
}
