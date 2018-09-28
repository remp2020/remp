<?php

namespace App\Contracts;

use Carbon\Carbon;

class JournalHelpers
{
    /**
     * Get time iterator, which is the earliest point of time (Carbon instance) that when
     * interval of length $intervalMinutes is added,
     * the resulting Carbon instance is greater or equal to $timeAfter
     *
     * This is useful for preparing data for histogram graphs
     *
     * @param Carbon $timeAfter
     * @param int    $intervalMinutes
     *
     * @return Carbon
     */
    public static function getTimeIterator(Carbon $timeAfter, int $intervalMinutes): Carbon
    {
        $timeIterator = (clone $timeAfter)->tz('UTC')->startOfDay();
        while ($timeIterator->lessThanOrEqualTo($timeAfter)) {
            $timeIterator->addMinutes($intervalMinutes);
        }
        return $timeIterator->subMinutes($intervalMinutes);
    }
}