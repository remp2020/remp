<?php

namespace App\Http\Controllers;

use App\Article;
use App\Contracts\JournalAggregateRequest;
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

        // TODO: change grouping to 'derived_referer_medium'
        $journalRequest = new JournalAggregateRequest('pageviews', 'load');
        $journalRequest->setTimeAfter($timeAfter);
        $journalRequest->setTimeBefore($timeBefore);
        $journalRequest->setTimeHistogram($intervalElastic, '0h');
        $journalRequest->addGroup('social');
        $currentRecords = $this->journal->count($journalRequest);

        $i = 0;
        foreach ($currentRecords as $currentRecord) {
            $label = $currentRecord->tags->social;
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

            // TODO: change grouping to 'derived_referer_medium'
            $journalRequest = new JournalAggregateRequest('pageviews', 'load');
            $journalRequest->setTimeAfter($timeAfterPrevious);
            $journalRequest->setTimeBefore($timeBeforePrevious);
            $journalRequest->setTimeHistogram($intervalElastic, '0h');
            $journalRequest->addGroup('social');
            $previousRecords = $this->journal->count($journalRequest);

            $i = 0;
            foreach ($previousRecords as $previousRecord) {
                $label = $previousRecord->tags->social;
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
        $timeAfter = (clone $timeBefore)->subSeconds(300);

        $journalRequest = new JournalAggregateRequest('pageviews', 'load');
        $journalRequest->setTimeAfter($timeAfter);
        $journalRequest->setTimeBefore($timeBefore);
        $journalRequest->addGroup('article_id');

        // records are already sorted
        $records = $this->journal->count($journalRequest);

        $top20 = [];
        $i = 0;
        foreach ($records as $record) {
            if ($i >= 20) {
                break;
            }

            $obj = new \stdClass();
            $obj->count = $record->count;
            $obj->article_id = $record->tags->article_id;

            if (!$record->tags->article_id) {
                $obj->title = 'Landing page';
                $obj->landing_page = true;
            } else {
                $article = Article::where('external_id', $record->tags->article_id)->first();
                if (!$article) {
                    continue;
                }
                $obj->landing_page = false;
                $obj->title = $article->title;
                $obj->published_at = $article->published_at->toAtomString();
            }
            $top20[] = $obj;
            $i++;
        }

        return response()->json($top20);
    }
}
