<?php

namespace Remp\BeamModule\Tests\Unit;

use Remp\BeamModule\Helpers\Misc;
use Illuminate\Support\Carbon;
use Remp\BeamModule\Tests\TestCase;

class HelpersTest extends TestCase
{
    public function testTimespanInPast()
    {
        $now = Carbon::now();

        $tests = [
            ['4d', (clone $now)->subDays(4)],
            ['1d 2h', (clone $now)->subDays(1)->subHours(2)],
            ['11d 5h 5m', (clone $now)->subDays(11)->subHours(5)->subMinutes(5)],
            ['2d 7m', (clone $now)->subDays(2)->subMinutes(7)],
            ['1m', (clone $now)->subMinutes(1)],
        ];

        foreach ($tests as $test) {
            $past = Misc::timespanInPast($test[0], clone $now);
            $this->assertTrue($past->eq($test[1]), "Dates not equal for timespan $test[0]");
        }
    }
}
