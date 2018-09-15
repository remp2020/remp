<?php

namespace App\Http\Controllers;

use App\Article;
use App\Contracts\JournalAggregateRequest;
use App\Contracts\JournalConcurrentsRequest;
use App\Contracts\JournalContract;
use Carbon\Carbon;
use Illuminate\Http\Request;
use InvalidArgumentException;

class DashboardController extends Controller
{
    private $journal;

    public function __construct(JournalContract $journal)
    {

        $this->journal = $journal;
    }

    public function index()
    {
        return view('dashboard.index');
    }

    public function todayTimeHistogram(Request $request)
    {
        $request->validate([
            'tz' => 'timezone',
            'interval' => 'required|in:today,7days,30days',
        ]);

        $tz = new \DateTimeZone($request->get('tz', 'UTC'));

        $interval = $request->get('interval');
        switch ($interval) {
            case 'today':
                $timeBefore = Carbon::tomorrow($tz);
                $timeAfter = Carbon::today($tz);
                $intervalElastic = '20m';
                $intervalMinutes = 20;
                break;
            case '7days':
                $timeBefore = Carbon::tomorrow($tz);
                $timeAfter = Carbon::today($tz)->subDays(6);
                $intervalElastic = '1h';
                $intervalMinutes = 60;
                break;
            case '30days':
                $timeBefore = Carbon::tomorrow($tz);
                $timeAfter = Carbon::today($tz)->subDays(29);
                $intervalElastic = '2h';
                $intervalMinutes = 120;
                break;
            default:
                throw new InvalidArgumentException("Parameter 'interval' must be one of the [today,7days,30days] values, instead '$interval' provided");
        }

        $colorStack = [
            '#EED0BC',
            '#EB8459',
            '#D49BC4',
            '#50C8C8',
            '#4C91B8',
            '#FF9A21'
            ];

        // Compute labels
        $labels = [];
        $timeIterator = clone $timeAfter;
        while ($timeIterator->lessThan($timeBefore)) {
            $endTime = (clone $timeIterator)->addMinutes($intervalMinutes);
            if ($interval === 'today') {
                $labels[] = $timeIterator->format('H:i') . ' - ' . $endTime->format('H:i');
            } else if ($interval === '7days') {
                $labels[] = $timeIterator->format('l H:i') . ' - ' . $endTime->format('l H:i');
            } else {
                $labels[] = $timeIterator->format('d.m H:i') . ' - ' . $endTime->format('d.m. H:i');
            }
            $timeIterator->addMinutes($intervalMinutes);
        }

        $series = [];

        // What part of today we should draw (omit 0 values)
        $numberOfCurrentValues = (int) floor((Carbon::now($tz)->getTimestamp() - $timeAfter->getTimestamp()) / ($intervalMinutes * 60));

        $journalRequest = new JournalAggregateRequest('pageviews', 'load');
        $journalRequest->setTimeAfter($timeAfter);
        $journalRequest->setTimeBefore($timeBefore);
        $journalRequest->setTimeHistogram($intervalElastic, '0h');
        $journalRequest->addGroup('derived_referer_medium');
        $currentRecords = $this->journal->count($journalRequest);

        $i = 0;
        foreach ($currentRecords as $currentRecord) {
            $label = $currentRecord->tags->derived_referer_medium;
            $currentValues = collect($currentRecord->time_histogram)->pluck('value')->take($numberOfCurrentValues);

            // Preparing data for echarts
            $series[] = [
                'name' => 'current_' . ucfirst($label),
                'type' => 'line',
                'stack' => 'current',
                'symbol' => 'none',
                'data' => $currentValues,
                'areaStyle' => [
                    'color' => $colorStack[$i],
                    'opacity' => '1'

                ],
                'lineStyle' => [
                    'color' => $colorStack[$i],
                    'width' => 1,
                    'opacity' => '1'
                ]
            ];
            $i++;
        }

        // Compute shadow values from previous week for today/7-days intervals
        if ($interval !== '30days') {
            $timeBeforePrevious = (clone $timeBefore)->subWeek();
            $timeAfterPrevious = (clone $timeAfter)->subWeek();

            $journalRequest = new JournalAggregateRequest('pageviews', 'load');
            $journalRequest->setTimeAfter($timeAfterPrevious);
            $journalRequest->setTimeBefore($timeBeforePrevious);
            $journalRequest->setTimeHistogram($intervalElastic, '0h');
            $journalRequest->addGroup('derived_referer_medium');
            $previousRecords = $this->journal->count($journalRequest);

            $i = 0;
            foreach ($previousRecords as $previousRecord) {
                $label = $previousRecord->tags->derived_referer_medium;
                $previousHistogram = collect($previousRecord->time_histogram);
                $previousValues = $previousHistogram->pluck('value');

                $series[] = [
                    'name' => 'previous_' . ucfirst($label),
                    'type' => 'line',
                    'stack' => 'previous',
                    'symbol' => 'none',
                    'data' => $previousValues,
                    'areaStyle' => [
                        'color' => '#e1e1e1',
                        'opacity' => '0.5'
                    ],
                    'lineStyle' => [
                        'width' => 1,
                        'color' => '#e1e1e1',
                        'opacity' => $i === 0 ? 0 : 1
                    ]
                ];
                $i++;
            }
        }

        $data = [
            'series' => $series,
            'xaxis' => $labels,
            'colors' => $colorStack,
        ];

        return response()->json($data);
    }

    public function mostReadArticles()
    {
        $timeBefore = Carbon::now();
        $timeAfter = (clone $timeBefore)->subSeconds(600); // Last 10 minutes

        $concurrentsRequest = new JournalConcurrentsRequest();
        $concurrentsRequest->setTimeAfter($timeAfter);
        $concurrentsRequest->setTimeBefore($timeBefore);
        $concurrentsRequest->addGroup('article_id');

        // records are already sorted
        $records = $this->journal->concurrents($concurrentsRequest);

        $minimalPublishedTime = Carbon::now();

        // Load articles details
        $top20 = [];
        $i = 0;
        foreach ($records as $record) {
            if ($i >= 20) {
                break;
            }

            $obj = new \stdClass();
            $obj->count = $record->count;
            $obj->external_article_id = $record->tags->article_id;

            if (!$record->tags->article_id) {
                $obj->title = 'Landing page';
                $obj->landing_page = true;
            } else {
                $article = Article::where('external_id', $record->tags->article_id)->first();
                if (!$article) {
                    continue;
                }
                if ($minimalPublishedTime->gt($article->published_at)) {
                    $minimalPublishedTime = $article->published_at;
                }
                $obj->landing_page = false;
                $obj->title = $article->title;
                $obj->published_at = $article->published_at->toAtomString();
                $obj->conversions_count = $article->conversions->count();
                $obj->article = $article;
            }
            $top20[] = $obj;
            $i++;
        }

        // Load timespent
        $timespentRequest = new JournalAggregateRequest('pageviews', 'timespent');
        // we compute average spent time of most read articles as average of last 2 hours
        $timespentRequest->setTimeAfter((clone $timeAfter)->subHours(2));
        $timespentRequest->setTimeBefore($timeBefore);
        $timespentRequest->addGroup('article_id');

        $externalArticleIds = collect($top20)->filter(function ($item) {
            return !empty($item->external_article_id);
        })->pluck('external_article_id');

        $timespentRequest->addFilter('article_id', ...$externalArticleIds);
        $articleIdToTimespent = $this->journal->avg($timespentRequest)->mapWithKeys(function ($item) {
            return [$item->tags->article_id => $item->avg];
        });

        // Load unique pageloads
        $uniqueRequest = new JournalAggregateRequest('pageviews', 'browsers');
        $uniqueRequest->setTimeAfter($minimalPublishedTime);
        $uniqueRequest->setTimeBefore($timeBefore);
        $uniqueRequest->addGroup('article_id');
        $uniqueRequest->addFilter('article_id', ...$externalArticleIds);
        $articleIdToUniqueBrowsersCount = $this->journal->unique($uniqueRequest)->mapWithKeys(function ($item) {
            return [$item->tags->article_id => $item->count];
        });

        foreach ($top20 as $item) {
            if ($item->external_article_id) {
                $secondsTimespent = $articleIdToTimespent->get($item->external_article_id, 0);
                $item->avg_timespent_string = $secondsTimespent >= 3600 ?
                    gmdate('H:i:s', $secondsTimespent) :
                    gmdate('i:s', $secondsTimespent);
                $item->unique_browsers_count = $articleIdToUniqueBrowsersCount[$item->external_article_id];
                $item->conversion_rate = $item->conversions_count / $item->unique_browsers_count;
            }
        }

        return response()->json($top20);
    }
}
