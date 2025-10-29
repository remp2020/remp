<?php

namespace Remp\CampaignModule\Models\Interval;

use LogicException;

enum IntervalModeEnum: string
{
    case Auto = 'auto';
    case Year = 'year';
    case Month = 'month';
    case Week = 'week';
    case Day = 'day';
    case Hour = 'hour';
    case Min15 = '15min';
    case Min5 = '5min';

    public function toInterval(): Interval
    {
        return match ($this) {
            self::Year => new Interval('8760h', 'month', 12), // 365 days * 24 hours
            self::Month => new Interval('720h', 'day', 30), // 30 days * 24 hours
            self::Week => new Interval('168h', 'week', 1), // 7 days * 24 hours
            self::Day => new Interval('24h', 'day', 1),
            self::Hour => new Interval('3600s', 'hour', 1), // 60 minutes * 60 seconds
            self::Min15 => new Interval('900s', 'minute', 15), // 15 minutes * 60 seconds
            self::Min5 => new Interval('300s', 'minute', 5), // 5 minutes * 60 seconds
            self::Auto => throw new LogicException('Auto interval mode should be handled by calcInterval logic'),
        };
    }

    public function minRangeSeconds(): int
    {
        return match ($this) {
            self::Year => 5 * 365 * 24 * 3600, // > 5 years
            self::Month => 2 * 365 * 24 * 3600, // > 2 years
            self::Week => 90 * 24 * 3600, // > 90 days
            self::Day => 3 * 24 * 3600, // > 3 days
            self::Hour => 8 * 3600, // > 8 hours
            self::Min15 => 2 * 3600, // >= 2 hours
            self::Min5 => 0, // No minimum (fallback)
            self::Auto => throw new LogicException('Auto mode has no range threshold'),
        };
    }
}
