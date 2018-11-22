<?php

namespace App\Model;

interface Aggregable
{

    /**
     * These fields will be aggregated after 90 days to save DB space by CompressAggregations command
     * @return array
     */
        public function aggregatedFields(): array;
}
