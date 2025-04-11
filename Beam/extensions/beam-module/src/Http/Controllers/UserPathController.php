<?php

namespace Remp\BeamModule\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Remp\BeamModule\Http\Requests\ConversionsSankeyRequest;
use Remp\BeamModule\Http\Resources\ConversionsSankeyResource;
use Remp\BeamModule\Model\Author;
use Remp\BeamModule\Model\Charts\ConversionsSankeyDiagram;
use Remp\BeamModule\Model\Conversion;
use Remp\BeamModule\Model\ConversionCommerceEvent;
use Remp\BeamModule\Model\ConversionGeneralEvent;
use Remp\BeamModule\Model\ConversionPageviewEvent;
use Remp\BeamModule\Model\ConversionSource;
use Remp\BeamModule\Model\Section;
use Remp\Journal\JournalContract;

class UserPathController extends Controller
{
    private $journal;

    public function __construct(JournalContract $journal)
    {
        $this->journal = $journal;
    }

    public function index()
    {
        $authors = Author::all();
        $sections = Section::all();
        $sumCategories = Conversion::select('amount', 'currency')->groupBy('amount', 'currency')->get();

        return view('beam::userpath.index', [
            'authors' => $authors,
            'sections' => $sections,
            'days' => range(1, 14),
            'sumCategories' => $sumCategories,
            'conversionSourceTypes' => ConversionSource::getTypes()
        ]);
    }

    public function stats(Request $request)
    {
        $days = (int) $request->get('days');
        $minutes = $days * 24 * 60;
        $authors = $request->get('authors', []);
        $sections = $request->get('sections', []);
        $sums = $request->get('sums', []);

        $conversionsQuery = Conversion::select('conversions.*')
            ->join('articles', 'articles.id', '=', 'conversions.article_id')
            ->join('article_author', 'articles.id', '=', 'article_author.article_id')
            ->join('article_section', 'articles.id', '=', 'article_section.article_id');

        if ($authors) {
            $conversionsQuery->whereIn('article_author.author_id', $authors);
        }

        if ($sections) {
            $conversionsQuery->whereIn('article_section.section_id', $sections);
        }

        if ($sums) {
            $conversionsQuery->where(function ($query) use ($sums) {
                foreach ($sums as $sum) {
                    $query->orWhere(function ($query) use ($sum) {
                        [$amount, $currency] = explode('|', $sum, 2);
                        $query->where('conversions.amount', $amount);
                        $query->where('conversions.currency', $currency);
                    });
                }
            });
        }

        $lastActionsLimit = 5;

        // commerce events
        $commerceEventsQuery = ConversionCommerceEvent::where('minutes_to_conversion', '<=', $minutes)
            ->select('step', 'funnel_id', DB::raw('count(*) as group_count'))
            ->groupBy('step', 'funnel_id')
            ->orderByDesc('group_count');

        $commerceLastEventsQuery = ConversionCommerceEvent::where('minutes_to_conversion', '<=', $minutes)
            ->select('step', DB::raw('count(*) as group_count'))
            ->groupBy('step')
            ->where('event_prior_conversion', '<=', $lastActionsLimit);

        // pageview events
        $pageviewEventsQuery = ConversionPageviewEvent::select(
            'locked',
            'signed_in',
            DB::raw('count(*) as group_count'),
            DB::raw('coalesce(avg(timespent), 0) as timespent_avg')
        )
            ->where('minutes_to_conversion', '<=', $minutes)
            ->groupBy('locked', 'signed_in')
            ->orderByDesc('group_count');

        $pageviewLastEventsQuery = ConversionPageviewEvent::where('minutes_to_conversion', '<=', $minutes)
            ->where('event_prior_conversion', '<=', $lastActionsLimit);

        // general events
        $generalEventsQuery = ConversionGeneralEvent::select('action', 'category', DB::raw('count(*) as group_count'))
            ->where('minutes_to_conversion', '<=', $minutes)
            ->groupBy('action', 'category')
            ->orderByDesc('group_count');

        $generalLastEventsQuery = ConversionGeneralEvent::select('action', 'category', DB::raw('count(*) as group_count'))
            ->where('event_prior_conversion', '<=', $lastActionsLimit)
            ->where('minutes_to_conversion', '<=', $minutes)
            ->groupBy('action', 'category');

        if ($authors || $sections || $sums) {
            $commerceJoin = function ($join) {
                $join->on('conversion_commerce_events.conversion_id', '=', 'c.id');
            };
            $pageviewJoin = function ($join) {
                $join->on('conversion_pageview_events.conversion_id', '=', 'c.id');
            };
            $generalJoin = function ($join) {
                $join->on('conversion_general_events.conversion_id', '=', 'c.id');
            };

            $commerceEventsQuery->joinSub($conversionsQuery, 'c', $commerceJoin);
            $pageviewEventsQuery->joinSub($conversionsQuery, 'c', $pageviewJoin);
            $generalEventsQuery->joinSub($conversionsQuery, 'c', $generalJoin);

            $commerceLastEventsQuery->joinSub($conversionsQuery, 'c', $commerceJoin);
            $pageviewLastEventsQuery->joinSub($conversionsQuery, 'c', $pageviewJoin);
            $generalLastEventsQuery->joinSub($conversionsQuery, 'c', $generalJoin);
        }

        $total = $pageviewCount = $pageviewLastEventsQuery->count();
        $absoluteCounts = [
            ['name' => 'pageview', 'count'=>$pageviewCount]
        ];

        foreach ($generalLastEventsQuery->get() as $event) {
            $absoluteCounts[] = [
                'name' => $event->action . ':' . $event->category,
                'count' => $event['group_count']
            ];
            $total += $event['group_count'];
        }

        foreach ($commerceLastEventsQuery->get() as $event) {
            $absoluteCounts[] = [
                'name' => $event->step,
                'count' => $event['group_count']
            ];
            $total += $event['group_count'];
        }

        return response()->json([
            'commerceEvents' => $commerceEventsQuery->get(),
            'pageviewEvents' => $pageviewEventsQuery->get(),
            'generalEvents' => $generalEventsQuery->get(),
            'lastEvents' => [
                'limit' => $lastActionsLimit,
                'absoluteCounts' => $absoluteCounts,
                'total' => $total
            ]
        ]);
    }

    public function diagramData(ConversionsSankeyRequest $request)
    {
        $from = Carbon::now($request->get('tz'))->subDays($request->get('interval'));
        $to = Carbon::now($request->get('tz'));

        $conversionSources = Conversion::whereBetween('paid_at', [$from, $to])
            ->with('conversionSources')
            ->has('conversionSources')
            ->get()
            ->pluck('conversionSources')
            ->flatten();

        $conversionsSankeyDiagram = new ConversionsSankeyDiagram($this->journal, $conversionSources, $request->get('conversionSourceType'));

        return new ConversionsSankeyResource($conversionsSankeyDiagram);
    }
}
