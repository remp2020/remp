<?php

namespace Remp\BeamModule\Http\Controllers;

use Remp\BeamModule\Contracts\GoogleAnalytics\GoogleAnalyticsReportingContract;
use Remp\BeamModule\Contracts\GoogleAnalytics\GoogleAnalyticsReportingRequest;
use Remp\BeamModule\Helpers\Journal\JournalHelpers;
use Remp\BeamModule\Helpers\Colors;
use Carbon\Carbon;
use Illuminate\Http\Request;
use InvalidArgumentException;

class GoogleAnalyticsReportingController extends Controller
{
    private $googleAnalyticsReportingContract;

    public function __construct(GoogleAnalyticsReportingContract $googleAnalyticsReportingContract)
    {
        $this->googleAnalyticsReportingContract = $googleAnalyticsReportingContract;
    }

    public function index()
    {
        return response()->view('beam::googleanalyticsreporting.index');
    }

    public function timeHistogram(Request $request)
    {
        $request->validate([
            'tz' => 'timezone',
            'interval' => 'required|in:7days,30days',
        ]);

        $tz = new \DateTimeZone($request->get('tz', 'UTC'));

        $interval = $request->get('interval');
        switch ($interval) {
            case '7days':
                $timeBefore = Carbon::now($tz);
                $timeAfter = Carbon::today($tz)->subDays(6);
                $intervalMinutes = 1440;
                break;
            case '30days':
                $timeBefore = Carbon::now($tz);
                $timeAfter = Carbon::today($tz)->subDays(29);
                $intervalMinutes = 1440;
                break;
            default:
                throw new InvalidArgumentException("Parameter 'interval' must be one of the [7days,30days] values, instead '$interval' provided");
        }

        $metric = 'ga:sessions';
        $dimension = 'ga:browser'; // ga:browserSize, ga:country
        $gaRequest = new GoogleAnalyticsReportingRequest();
        $gaRequest->setStartDate($timeAfter);
        $gaRequest->setEndDate($timeBefore);
        $gaRequest->setViewID(config('google.ga_view_id'));
        $gaRequest->addMetric($metric);
        $gaRequest->addDimension($dimension);
        $gaRequest->addDimension('ga:date');
        $gaRequest->addOrderBy([
                'field_name' => $metric,
                'sort_order' => 'DESCENDING',
        ]);
        $gaRequest->addMetricFilter([
            'metric_name' => $metric,
            'operator' => 'GREATER_THAN',
            'comparsion_value' => '13000',
        ]);
        $report = $this->googleAnalyticsReportingContract->report($gaRequest);

        $tags = [];
        foreach ($report as $item) {
            $tags[] = $item['dimensions'][$dimension];
        }
        $tags = array_values(array_unique($tags));

        $timeIterator = JournalHelpers::getTimeIterator($timeAfter, $intervalMinutes, $tz);

        $results = [];
        while ($timeIterator->lessThan($timeBefore)) {
            $zuluDate = $timeIterator->toIso8601ZuluString();

            $results[$zuluDate] = collect($tags)->mapWithKeys(function ($item) {
                return [$item => 0];
            });
            $results[$zuluDate]['Date'] = $zuluDate;

            $timeIterator->addMinutes($intervalMinutes);
        }

        foreach ($report as $item) {
            $date = Carbon::createFromFormat('Ymd H:i:s', $item['dimensions']['ga:date'] . ' 00:00:00', $tz)->toIso8601ZuluString();
            $results[$date][$item['dimensions'][$dimension]] = $item['metrics'][$metric];
        }

        $results = array_values($results);

        return response()->json([
            'intervalMinutes' => $intervalMinutes,
            'results' => $results,
            'tags' => $tags,
            'colors' => array_values(Colors::assignColorsToGeneralTags($tags))
        ]);
    }
}
