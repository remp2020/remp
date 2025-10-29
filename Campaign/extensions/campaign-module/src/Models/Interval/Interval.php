<?php

namespace Remp\CampaignModule\Models\Interval;

readonly class Interval
{
    /**
     * @param string $interval Elasticsearch interval format (e.g., '24h', '3600s', '900s')
     * @param string $timeUnit Chart.js time unit for display (e.g., 'month', 'day', 'hour', 'minute')
     * @param int $stepSize Chart.js step size for axis labels (e.g., 1, 12, 15)
     *
     * @see IntervalModeEnum::toInterval() For usage examples
     */
    public function __construct(
        public string $interval,
        public string $timeUnit,
        public int $stepSize,
    ) {
    }
}
