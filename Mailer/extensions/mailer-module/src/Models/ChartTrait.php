<?php

namespace Remp\MailerModule\Models;

trait ChartTrait
{
    protected function getChartSuggestedMin($groupedData): ?int
    {
        $globalMax = PHP_INT_MIN;
        $globalMin = PHP_INT_MAX;
        foreach ($groupedData as $xData => $yDataArray) {
            if (($localMin = min($yDataArray)) < $globalMin) {
                $globalMin = $localMin;
            }
            if (($localMax = max($yDataArray)) > $globalMax) {
                $globalMax = $localMax;
            }
        }

        // If the difference between chart extremes is greater than 30%, the chart should be readable without
        // any further manipulation.
        if ($globalMin < $globalMax * 0.7) {
            return null;
        }

        $viewWindowMin = floor($globalMin * 0.90);
        return max($viewWindowMin, 0);
    }
}
