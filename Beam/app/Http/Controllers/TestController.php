<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Class TestController
 * Temporary controller for testing author segments conditions, not visible in menu
 * TODO: delete after autor segment rules are specified
 * @package App\Http\Controllers
 */
class TestController extends Controller
{
    public function authorSegmentsTest()
    {
        return view('test.form');
    }

    public function showResults(Request $request)
    {
        $minimalViews = $request->get('min_views');
        $minimalAverageTimespent = $request->get('min_average_timespent');
        $minimalRatio = $request->get('min_ratio');
        $history = (int) $request->get('history');
        $fromDay = Carbon::now()->subDays($history)->toDateString();

        // only 30, 60 and 90 are allowed values
        $columnDays = 'total_views_last_' . $history .'_days';

        $results = [];

        $resultsBrowsers = DB::select("select T.author_id, authors.name, count(*) as browser_count from
(select browser_id, author_id, sum(pageviews) as author_browser_views, avg(timespent) as average_timespent 
from article_aggregated_views C join article_author A on A.article_id = C.article_id
where timespent <= 3600 
and date >= ?
group by browser_id, author_id
having 
author_browser_views >= ? and
average_timespent >= ? and
author_browser_views/(select $columnDays from views_per_browser_mv where browser_id = C.browser_id) >= ?
) T join authors on authors.id = T.author_id
group by author_id order by browser_count desc", [$fromDay, $minimalViews, $minimalAverageTimespent, $minimalRatio]);

        foreach ($resultsBrowsers as $item) {
            $obj = new \stdClass();
            $obj->name = $item->name;
            $obj->browser_count = $item->browser_count;
            $obj->user_count = 0;

            $results[$item->author_id] = $obj;
        }

        $resultsUsers = DB::select("select T.author_id, authors.name, count(*) as user_count from
(select user_id, author_id, sum(pageviews) as author_user_views, avg(timespent) as average_timespent 
from article_aggregated_views C join article_author A on A.article_id = C.article_id
where timespent <= 3600 
and user_id <> ''
and date >= ?
group by user_id, author_id
having 
author_user_views >= ? and
average_timespent >= ? and
author_user_views/(select $columnDays from views_per_user_mv where user_id = C.user_id) >= ?
) T join authors on authors.id = T.author_id
group by author_id order by user_count desc", [$fromDay, $minimalViews, $minimalAverageTimespent, $minimalRatio]);

        foreach ($resultsUsers as $item) {
            if (!array_key_exists($item->author_id, $results)) {
                $obj = new \stdClass();
                $obj->name = $item->name;
                $obj->browser_count = 0;
                $obj->user_count = 0;
            }

            $results[$item->author_id]->user_count = $item->user_count;
        }

        $results = collect($results)->sortByDesc('browser_count');

        return view('test.form', [
            'results' => $results,
            'history' => $history,
            'min_views' => $minimalViews,
            'min_ratio' => $minimalRatio,
            'min_average_timespent' => $minimalAverageTimespent
        ]);
    }
}
