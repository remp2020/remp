<?php

namespace App\Helpers;

use Illuminate\Support\Carbon;

class Misc
{
    /**
     * Converts timespan string to Carbon object in past
     * @param string      $timespan format specifying number of days, hours and minutes, e.g. 3d 4h 5m
     * @param Carbon|null $from
     *
     * @return Carbon
     */
    public static function timespanInPast(string $timespan, Carbon $from = null): Carbon
    {
        $c = $from ?? Carbon::now();

        $matches = [];
        preg_match("/^(?:(\d+)d)?\s*(?:(\d+)h)?\s*(?:(\d+)m)?$/i", $timespan, $matches);

        if ($matches[1] ?? false) {
            $c->subDays($matches[1]);
        }
        if ($matches[2] ?? false) {
            $c->subHours($matches[2]);
        }
        if ($matches[3] ?? false) {
            $c->subMinutes($matches[3]);
        }
        return $c;
    }
}
