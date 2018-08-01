<?php

namespace App\Http\Controllers;

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

        $results = DB::select('select T.author_id, authors.name, count(*) as browser_count from
(select browser_id, author_id, sum(pageviews) as author_browser_views, avg(timespent) as average_timespent 
from article_aggregated_views C join article_author A on A.article_id = C.article_id
where timespent <= 3600 
and browser_id <> \'\'
group by browser_id, author_id
having 
author_browser_views >= ? and
average_timespent >= ? and
author_browser_views/(select total_views from views_per_browser_mv where browser_id = C.browser_id) >= ?
) T join authors on authors.id = T.author_id
group by author_id order by browser_count desc', [ $minimalViews, $minimalAverageTimespent, $minimalRatio]);

        return view('test.form', [
            'results' => $results,
            'min_views' => $minimalViews,
            'min_ratio' => $minimalRatio,
            'min_average_timespent' => $minimalAverageTimespent
        ]);
    }
}
