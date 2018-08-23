<?php

namespace App\Http\Controllers;

use App\Article;
use App\Contracts\JournalAggregateRequest;
use App\Contracts\JournalContract;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
        $tz = new \DateTimeZone($request->get('tz', 'UTC'));

        $timeBefore = Carbon::tomorrow($tz);
        $timeAfter = Carbon::today($tz);

        // TODO: change grouping to 'derived_referer_medium'
        $journalRequest = new JournalAggregateRequest('pageviews', 'load');
        $journalRequest->setTimeAfter($timeAfter);
        $journalRequest->setTimeBefore($timeBefore);
        $journalRequest->setTimeHistogram('20m', '0h');
        $journalRequest->addGroup('social');
        $currentRecords = $this->journal->count($journalRequest);

        $timeBefore = $timeBefore->subWeek();
        $timeAfter = $timeAfter->subWeek();

        $journalRequest = new JournalAggregateRequest('pageviews', 'load');
        $journalRequest->setTimeAfter($timeAfter);
        $journalRequest->setTimeBefore($timeBefore);
        $journalRequest->setTimeHistogram('20m', '0h');
        $journalRequest->addGroup('social');
        $previousRecords = $this->journal->count($journalRequest);

        $series = [];
        $labels = null;

        $colorStack = [
            '#EED0BC',
            '#EB8459',
            '#D49BC4',
            '#50C8C8',
            '#4C91B8',
            '#FF9A21'
            ];

        $i = 0;
        foreach ($previousRecords as $previousRecord) {
            $label = $previousRecord->tags->social;
            $previousHistogram = collect($previousRecord->time_histogram);
            $previousValues = $previousHistogram->pluck('value');

            if ($labels === null){
                $labels = $previousHistogram->pluck('time')->map(function($value) use ($tz){
                    $startTime = (new Carbon($value))->tz($tz);
                    $endTime = (clone $startTime)->addMinutes(20);
                    return $startTime->format('H:i') . ' - ' . $endTime->format('H:i');
                })->toArray();
            }

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

        // What part of the current day we should draw (1200 - number of seconds per 20m)
        $numberOfCurrentValues = (int) floor((Carbon::now($tz)->getTimestamp() - Carbon::today($tz)->getTimestamp()) / 1200);
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
                    'opacity' => '0.9'

                ],
                'lineStyle' => [
                    'color' => $colorStack[$i],
                    'width' => 1
                ]
            ];
            $i++;
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
