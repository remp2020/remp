<?php

namespace Remp\BeamModule\Contracts\GoogleAnalytics;

use Google_Service_AnalyticsReporting;
use Google_Service_AnalyticsReporting_DateRange;
use Google_Service_AnalyticsReporting_Dimension;
use Google_Service_AnalyticsReporting_GetReportsRequest;
use Google_Service_AnalyticsReporting_Metric;
use Google_Service_AnalyticsReporting_MetricFilter;
use Google_Service_AnalyticsReporting_MetricFilterClause;
use Google_Service_AnalyticsReporting_OrderBy;
use Google_Service_AnalyticsReporting_ReportRequest;
use Illuminate\Support\Collection;

class GoogleAnalyticsReporting implements GoogleAnalyticsReportingContract
{
    private $client;

    public function __construct(Google_Service_AnalyticsReporting $client)
    {
        $this->client = $client;
    }

    public function report(GoogleAnalyticsReportingRequest $request): Collection
    {
        // create DateRange object
        $dateRange = new Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate($request->getStartDate());
        $dateRange->setEndDate($request->getEndDate());

        // create Metric objects
        $metrics = [];
        foreach ($request->getMetrics() as $metricExpression) {
            $metric = new Google_Service_AnalyticsReporting_Metric();
            $metric->setExpression($metricExpression);
            $metrics[] = $metric;
        }

        // create Dimension objects
        $dimensions = [];
        foreach ($request->getDimensions() as $dimensionName) {
            $dimension = new Google_Service_AnalyticsReporting_Dimension();
            $dimension->setName($dimensionName);
            $dimensions[] = $dimension;
        }

        // create OrderBy objects
        $orderBys = [];
        foreach ($request->getOrderBys() as $orderByArray) {
            $orderBy = new Google_Service_AnalyticsReporting_OrderBy();
            $orderBy->setFieldName($orderByArray['field_name']);
            $orderBy->setSortOrder($orderByArray['sort_order']);
            $orderBys[] = $orderBy;
        }

        // create MetricFilter objects
        $metricFilters = [];
        foreach ($request->getMetricFilters() as $metricFilterArray) {
            $metricFilter = new Google_Service_AnalyticsReporting_MetricFilter();
            $metricFilter->setMetricName($metricFilterArray['metric_name']);
            $metricFilter->setOperator($metricFilterArray['operator']);
            $metricFilter->setComparisonValue($metricFilterArray['comparsion_value']);
            $metricFilters[] = $metricFilter;
        }
        $metricFilterClause = new Google_Service_AnalyticsReporting_MetricFilterClause();
        $metricFilterClause->setFilters($metricFilter);

        // create ReportRequest object
        $reportRequest = new Google_Service_AnalyticsReporting_ReportRequest();
        $reportRequest->setViewId($request->getViewID());
        $reportRequest->setDateRanges($dateRange);
        $reportRequest->setDimensions($dimensions);
        $reportRequest->setMetrics($metrics);
        $reportRequest->setOrderBys($orderBys);
        $reportRequest->setMetricFilterClauses([$metricFilterClause]);

        // get report & collect results
        $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests([$reportRequest]);
        $reports = $this->client->reports->batchGet($body);
        $results = $this->collectResults($reports);

        return collect($results);
    }

    private function collectResults($reports)
    {
        for ($reportIndex = 0; $reportIndex < count($reports); $reportIndex++) {
            $report = $reports[ $reportIndex ];
            $header = $report->getColumnHeader();
            $dimensionHeaders = $header->getDimensions();
            $metricHeaders = $header->getMetricHeader()->getMetricHeaderEntries();
            $rows = $report->getData()->getRows();

            $results = [];

            for ($rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
                $row = $rows[ $rowIndex ];
                $dimensions = $row->getDimensions();
                $metrics = $row->getMetrics();
                $result = [];
                if ($dimensionHeaders !== null) {
                    for ($i = 0; $i < count($dimensionHeaders) && $i < count($dimensions); $i++) {
                        $result['dimensions'][$dimensionHeaders[$i]] = $dimensions[$i];
                    }
                }

                for ($j = 0; $j < count($metrics); $j++) {
                    $values = $metrics[$j]->getValues();
                    for ($k = 0; $k < count($values); $k++) {
                        $entry = $metricHeaders[$k];
                        $result['metrics'][$entry->getName()] = $values[$k];
                    }
                }

                $results[] = $result;
            }
        }
        return $results;
    }
}
