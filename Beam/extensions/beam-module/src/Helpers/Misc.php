<?php

namespace Remp\BeamModule\Helpers;

use Carbon\CarbonInterval;
use Illuminate\Support\Carbon;

class Misc
{
    // e.g. 3d 4h 2m
    const TIMESPAN_VALIDATION_REGEX = '/^(\d+d)?\s*(\d+h)?\s*(\d+m)?$/i';

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

    /**
     * Convert any Carbon parseable date to format that can be safely used in SQL (with correct time zone)
     * TODO: this can be removed after update to Laravel 8 and getting rid of upsert, see Article#upsert method
     * @param $date
     *
     * @return string
     */
    public static function dateToSql($date): string
    {
        return Carbon::parse($date)
            ->tz(date_default_timezone_get())
            ->toDateTimeString();
    }

    public static function secondsIntoReadableTime(int $seconds): string
    {
        if ($seconds === 0) {
            return "0s";
        }

        $output = '';
        $interval = CarbonInterval::seconds($seconds)->cascade();
        if ($interval->hours > 0) {
            $output .= "{$interval->hours}h ";
        }

        if ($interval->minutes > 0) {
            $output .= "{$interval->minutes}m ";
        }

        if ($interval->seconds > 0) {
            $output .= "{$interval->seconds}s";
        }

        return trim($output);
    }
}
