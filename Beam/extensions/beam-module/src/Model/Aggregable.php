<?php

namespace Remp\BeamModule\Model;

interface Aggregable
{
    /**
     * These fields will be aggregated (= summed for that particular day) after 90 days
     * to save DB space by CompressAggregations command
     * @return array
     */
    public function aggregatedFields(): array;

    /**
     * When doing aggregation, we want put sum of aggregated fields of these columns into different buckets
     * e.g. we want to distinguish between referer sources (email, direct)
     * and do not aggregate them together for that particular day
     * @return array
     */
    public function groupableFields(): array;
}
