<?php

namespace Remp\BeamModule\Contracts\GoogleAnalytics;

class GoogleAnalyticsReportingRequest
{
    /** @var \DateTime */
    protected $startDate;

    /** @var \DateTime */
    protected $endDate;

    /** @var string */
    protected $viewID;

    /** @var array */
    protected $metrics = [];

    /** @var array */
    protected $dimensions = [];

    /** @var array */
    protected $orderBys = [];

    /** @var array */
    protected $metricFilters = [];

    public function setStartDate(\DateTime $startDate)
    {
        $this->startDate = $startDate;
    }

    public function setEndDate(\DateTime $endDate)
    {
        $this->endDate = $endDate;
    }

    public function setViewID(string $viewID)
    {
        $this->viewID = $viewID;
    }

    /**
     * Adds metric
     *
     * Use API name from https://developers.google.com/analytics/devguides/reporting/core/dimsmets#mode=api
     *
     * @param string $metric
     */
    public function addMetric(string $metric)
    {
        $metric = trim($metric);
        if (!empty($metric)) {
            $this->metrics[] = $metric;
        }
    }

    /**
     * Adds dimension
     *
     * Use API name from https://developers.google.com/analytics/devguides/reporting/core/dimsmets#mode=api
     * @param string $dimension
     */
    public function addDimension(string $dimension)
    {
        $dimension = trim($dimension);
        if (!empty($dimension)) {
            $this->dimensions[] = $dimension;
        }
    }

    /**
     * Adds new ordering rule
     *
     * Array $orderBy has to contain two elements:
     *
     * [
     *      'field_name' => string, // metric or dimension name
     *      'sort_order' => string, // options: 'ASCENDING', 'DESCENDING'
     * ]
     *
     * @param array $orderBy
     * @throws \Exception
     */
    public function addOrderBy(array $orderBy)
    {
        if (!isset($orderBy['field_name']) || empty(trim($orderBy['field_name']))) {
            throw new \Exception('Order by must contain field `field_name`.');
        }

        if (!isset($orderBy['sort_order']) || !in_array($orderBy['sort_order'], ['ASCENDING','DESCENDING'])) {
            throw new \Exception('Order by must contain field `sort_order`. It has to be one of [ASCENDING,DESCENDING].');
        }

        $this->orderBys[] = $orderBy;
    }

    /**
     * Adds new MetricFilter
     *
     * Array $metricFilter has to contain three elements:
     *
     * [
     *      'metric_name' => string,
     *      'operator' => string, // options: 'EQUAL', 'LESS_THAN', 'GREATER_THAN', 'IS_MISSING'
     *      'comparsion_value' => string,
     * ]
     *
     * @param array $metricFilter
     * @throws \Exception
     */
    public function addMetricFilter(array $metricFilter)
    {
        if (!isset($metricFilter['metric_name']) || empty(trim($metricFilter['metric_name']))) {
            throw new \Exception('Metric Filter by must contain field `metric_name`.');
        }

        if (!isset($metricFilter['operator']) || !in_array($metricFilter['operator'], ['EQUAL', 'LESS_THAN', 'GREATER_THAN', 'IS_MISSING'])) {
            throw new \Exception('Metric Filter by must contain field `operator`. It has to be one of [EQUAL,LESS_THAN,GREATER_THAN,IS_MISSING].');
        }

        if (!isset($metricFilter['comparsion_value']) || empty(trim($metricFilter['comparsion_value']))) {
            throw new \Exception('Metric Filter by must contain field `comparsion_value`.');
        }

        $this->metricFilters[] = $metricFilter;
    }

    public function getStartDate(): string
    {
        return $this->startDate->format('Y-m-d');
    }

    public function getEndDate(): string
    {
        return $this->endDate->format('Y-m-d');
    }

    public function getViewID(): string
    {
        return $this->viewID;
    }

    public function getMetrics(): array
    {
        return array_values(array_unique($this->metrics));
    }

    public function getDimensions(): array
    {
        return array_values(array_unique($this->dimensions));
    }

    public function getOrderBys(): array
    {
        return $this->orderBys;
    }

    public function getMetricFilters(): array
    {
        return $this->metricFilters;
    }
}
