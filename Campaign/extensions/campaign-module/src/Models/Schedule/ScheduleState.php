<?php

namespace Remp\CampaignModule\Models\Schedule;

use Carbon\Carbon;
use DateTimeInterface;
use Remp\CampaignModule\Schedule;

/**
 * Schedule state rules shared by the Eloquent model and the cached view.
 */
class ScheduleState
{
    public static function isRunning(string $status, ?DateTimeInterface $startTime, ?DateTimeInterface $endTime): bool
    {
        if (!self::endsInFuture($endTime)) {
            return false;
        }
        if ($status === Schedule::STATUS_EXECUTED) {
            return true;
        }
        return $status === Schedule::STATUS_READY && $startTime < Carbon::now();
    }

    public static function endsInFuture(?DateTimeInterface $endTime): bool
    {
        return $endTime === null || $endTime > Carbon::now();
    }
}
