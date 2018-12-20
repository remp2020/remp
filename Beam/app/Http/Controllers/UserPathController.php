<?php

namespace App\Http\Controllers;

use App\Author;
use App\Contracts\JournalContract;
use App\Conversion;
use App\Http\Request;
use App\Model\ConversionCommerceEvent;
use App\Model\ConversionGeneralEvent;
use App\Model\ConversionPageviewEvent;
use App\Section;
use Illuminate\Support\Facades\DB;

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

        return view('userpath.index', [
            'authors' => $authors,
            'sections' => $sections,
            'days' => range(1, 14),
            'sumCategories' => $sumCategories,
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

        $commerceEventsQuery = ConversionCommerceEvent::select('conversion_commerce_events.*')
            ->where('minutes_to_conversion', '<=', $minutes)
            ->select('step', 'funnel_id', DB::raw('count(*) as group_count'))
            ->groupBy('step', 'funnel_id')
            ->orderByDesc('group_count');

        $pageviewEventsQuery = ConversionPageviewEvent::select('conversion_pageview_events.*')
            ->where('minutes_to_conversion', '<=', $minutes)
            ->select(
                'locked',
                'signed_in',
                DB::raw('count(*) as group_count'),
                DB::raw('coalesce(avg(timespent), 0) as timespent_avg')
            )
            ->groupBy('locked', 'signed_in')
            ->orderByDesc('group_count');

        $generalEventsQuery = ConversionGeneralEvent::select('conversion_general_events.*')
            ->where('minutes_to_conversion', '<=', $minutes)
            ->select('action', 'category', DB::raw('count(*) as group_count'))
            ->groupBy('action', 'category')
            ->orderByDesc('group_count');

        if ($authors || $sections || $sums) {
            $commerceEventsQuery->joinSub($conversionsQuery, 'c', function ($join) {
                $join->on('conversion_commerce_events.conversion_id', '=', 'c.id');
            });

            $pageviewEventsQuery->joinSub($conversionsQuery, 'c', function ($join) {
                $join->on('conversion_pageview_events.conversion_id', '=', 'c.id');
            });

            $generalEventsQuery->joinSub($conversionsQuery, 'c', function ($join) {
                $join->on('conversion_general_events.conversion_id', '=', 'c.id');
            });
        }

        return response()->json([
            'commerceEvents' => $commerceEventsQuery->get(),
            'pageviewEvents' => $pageviewEventsQuery->get(),
            'generalEvents' => $generalEventsQuery->get(),
        ]);
    }
}
