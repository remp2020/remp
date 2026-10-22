<?php

namespace Remp\CampaignModule\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Remp\CampaignModule\Http\Showtime\ShowtimeTimer;

class ShowtimeTimerTest extends TestCase
{
    public function testRepeatedPhaseDurationsAreSummed()
    {
        $timer = new ShowtimeTimer(microtime(true));
        $timer->addDuration('fetch', microtime(true) - 0.002);
        $timer->addDuration('fetch', microtime(true) - 0.003);

        $durations = $timer->report()['durations_ms'];
        $this->assertGreaterThanOrEqual(5, $durations['fetch']);
        $this->assertLessThan(50, $durations['fetch']);
    }

    public function testTotalIsMeasuredFromRequestStart()
    {
        $timer = new ShowtimeTimer(microtime(true) - 0.1);

        $this->assertGreaterThanOrEqual(100, $timer->report()['durations_ms']['total']);
    }

    public function testCounts()
    {
        $timer = new ShowtimeTimer(microtime(true));
        $timer->increment('segment_checks');
        $timer->increment('segment_checks');
        $timer->increment('campaigns', 82);

        $this->assertSame(['segment_checks' => 2, 'campaigns' => 82], $timer->report()['counts']);
    }

    public function testReportIsDisabledByDefault()
    {
        $timer = new ShowtimeTimer(microtime(true));
        $this->assertFalse($timer->isReported());

        $timer->enableReport();
        $this->assertTrue($timer->isReported());
    }

    public function testServerTimingHeader()
    {
        $header = ShowtimeTimer::serverTimingHeader([
            'durations_ms' => ['fetch' => 1.5, 'total' => 12.25],
            'counts' => ['campaigns' => 82],
        ]);

        $this->assertSame('Server-Timing: fetch;dur=1.5, total;dur=12.25', $header);
    }
}
