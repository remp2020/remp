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

        $shouldBeKept[] = factory(ArticleViewsSnapshot::class)->create(['time' => $start]);
        $shouldBeExcluded[] = factory(ArticleViewsSnapshot::class)->create(['time' => (clone $start)->addMinutes(1)]);
        $shouldBeExcluded[] = factory(ArticleViewsSnapshot::class)->create(['time' => (clone $start)->addMinutes(2)]);
        $shouldBeExcluded[] = factory(ArticleViewsSnapshot::class)->create(['time' => (clone $start)->addMinutes(3)]);
        $shouldBeExcluded[] = factory(ArticleViewsSnapshot::class)->create(['time' => (clone $start)->addMinutes(4)]);

        $shouldBeKept[] = factory(ArticleViewsSnapshot::class)->create(['time' => (clone $start)->addMinutes( 6)]);
        $shouldBeExcluded[] = factory(ArticleViewsSnapshot::class)->create(['time' => (clone $start)->addMinutes(7)]);
        $shouldBeExcluded[] = factory(ArticleViewsSnapshot::class)->create(['time' => (clone $start)->addMinutes(8)]);

        $pointsToExclude = $this->snapshotHelpers->timePointsToExclude($start, (clone $start)->addMinutes(15), 5);

        foreach ($shouldBeExcluded as $item) {
            $this->assertTrue(in_array($item->time->toIso8601ZuluString(), $pointsToExclude));
        }

        foreach ($shouldBeKept as $item) {
            $this->assertFalse(in_array($item->time->toIso8601ZuluString(), $pointsToExclude));
        }
    }
}
