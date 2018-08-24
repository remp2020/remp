<?php

namespace App\Console\Commands;

use App\ArticleAggregatedView;
use App\Author;
use App\Mail\AuthorSegmentsResult;
use App\Segment;
use App\SegmentBrowser;
use App\SegmentGroup;
use App\SegmentUser;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CreateAuthorsSegments extends Command
{
    const TIMESPENT_IGNORE_THRESHOLD_SECS = 3600;

    const COMMAND = 'segments:create-author-segments';

    protected $signature = self::COMMAND . ' {min_views} {min_average_timespent} {min_ratio} {history} {email}';

    protected $description = "Generate authors' segments from aggregated pageviews and timespent data.";

    public function handle()
    {
        Log::debug('CreateAuthorsSegments job STARTED');

        $minimalViews = $this->argument('min_views');
        $minimalAverageTimespent = $this->argument('min_average_timespent');
        $minimalRatio = $this->argument('min_ratio');
        $history = $this->argument('history');
        $emailDest = $this->argument('email');
        $this->computeAuthorSegments($minimalViews, $minimalAverageTimespent, $minimalRatio, $history, $emailDest);

        // TODO: enable this after condition are specialized
        //$this->recomputeBrowsersForAuthorSegments();
        //$this->recomputeUsersForAuthorSegments();

        Log::debug('CreateAuthorsSegments job FINISHED');
    }

    /**
     * @param $minimalViews
     * @param $minimalAverageTimespent
     * @param $minimalRatio
     * @param $historyDays
     * @param $emailDest
     */
    private function computeAuthorSegments($minimalViews, $minimalAverageTimespent, $minimalRatio, $historyDays, $emailDest)
    {
        $results = [];
        $fromDay = Carbon::now()->subDays($historyDays)->toDateString();
        // only 30, 60 and 90 are allowed values
        $columnDays = 'total_views_last_' . $historyDays .'_days';

        $this->line("running browsers query");

        $browsersSql = <<<SQL
    SELECT T.author_id, authors.name, count(*) AS browser_count 
    FROM
      (SELECT browser_id, author_id, sum(pageviews) AS author_browser_views, avg(timespent) AS average_timespent
      FROM article_aggregated_views C JOIN article_author A ON A.article_id = C.article_id
      WHERE timespent <= 3600
      AND date >= ?
      GROUP BY browser_id, author_id
      HAVING
      author_browser_views >= ? AND
      average_timespent >= ? AND
      author_browser_views/(SELECT $columnDays FROM views_per_browser_mv WHERE browser_id = C.browser_id) >= ?
      ) T 
    JOIN authors ON authors.id = T.author_id
    GROUP BY author_id 
    ORDER BY browser_count DESC
SQL;
        $resultsBrowsers = DB::select($browsersSql, [$fromDay, $minimalViews, $minimalAverageTimespent, $minimalRatio]);

        foreach ($resultsBrowsers as $item) {
            $obj = new \stdClass();
            $obj->name = $item->name;
            $obj->browser_count = $item->browser_count;
            $obj->user_count = 0;

            $results[$item->author_id] = $obj;
        }

        $this->line("running users query");

        $usersSql = <<<SQL
    SELECT T.author_id, authors.name, count(*) AS user_count 
    FROM
        (SELECT user_id, author_id, sum(pageviews) AS author_user_views, avg(timespent) AS average_timespent
        FROM article_aggregated_views C JOIN article_author A ON A.article_id = C.article_id
        WHERE timespent <= 3600
        AND user_id <> ''
        AND date >= ?
        GROUP BY user_id, author_id
        HAVING
        author_user_views >= ? AND
        average_timespent >= ? AND
        author_user_views/(SELECT $columnDays FROM views_per_user_mv WHERE user_id = C.user_id) >= ?
        ) T JOIN authors ON authors.id = T.author_id
    GROUP BY author_id ORDER BY user_count DESC
SQL;
        
        $resultsUsers = DB::select($usersSql, [$fromDay, $minimalViews, $minimalAverageTimespent, $minimalRatio]);

        foreach ($resultsUsers as $item) {
            if (!array_key_exists($item->author_id, $results)) {
                $obj = new \stdClass();
                $obj->name = $item->name;
                $obj->browser_count = 0;
                $obj->user_count = 0;
                $results[$item->author_id] = $obj;
            }

            $results[$item->author_id]->user_count = $item->user_count;
        }

        Mail::to($emailDest)->send(
            new AuthorSegmentsResult($results, $minimalViews, $minimalAverageTimespent, $minimalRatio, $historyDays)
        );
    }

    private function getOrCreateAuthorSegment($authorId)
    {
        $segmentGroup = SegmentGroup::where(['code' => SegmentGroup::CODE_AUTHORS_SEGMENTS])->first();
        $author = Author::find($authorId);

        return Segment::updateOrCreate([
            'code' => 'author-' . $author->id
        ], [
            'name' => 'Author ' . $author->name,
            'active' => true,
            'segment_group_id' => $segmentGroup->id,
        ]);
    }

    private function recomputeUsersForAuthorSegments()
    {
        $authorUsers = $this->groupDataFor('user_id');

        SegmentUser::truncate();

        foreach ($authorUsers as $authorId => $users) {
            $segment = $this->getOrCreateAuthorSegment($authorId);
            $toInsert = collect($users)->map(function ($userId) use ($segment) {
                return [
                    'segment_id' => $segment->id,
                    'user_id' => $userId,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            });
            SegmentUser::insert($toInsert->toArray());
        }
    }

    private function recomputeBrowsersForAuthorSegments()
    {
        $authorBrowsers = $this->groupDataFor('browser_id');

        SegmentBrowser::truncate();

        foreach ($authorBrowsers as $authorId => $browsers) {
            $segment = $this->getOrCreateAuthorSegment($authorId);
            $toInsert = collect($browsers)->map(function ($browserId) use ($segment) {
                return [
                    'segment_id' => $segment->id,
                    'browser_id' => $browserId,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            });
            SegmentBrowser::insert($toInsert->toArray());
        }
    }

    private function aggregatedPageviewsFor($groupParameter)
    {
        $results = [];
        $queryItems =  ArticleAggregatedView::select(
            DB::raw("$groupParameter, sum(pageviews) as total_pageviews")
        )
            ->join('article_author', 'article_author.article_id', '=', 'article_aggregated_views.article_id')
            ->where('timespent', '<=', self::TIMESPENT_IGNORE_THRESHOLD_SECS)
            ->whereRaw("$groupParameter <> ''")
            ->groupBy($groupParameter)
            ->cursor();

        foreach ($queryItems as $item) {
            $results[$item->$groupParameter] = (int) $item->total_pageviews;
        }
        return $results;
    }

    private function groupDataFor($groupParameter)
    {
        $totalPageviews = $this->aggregatedPageviewsFor($groupParameter);

        $queryItems =  ArticleAggregatedView::select(
            DB::raw("$groupParameter, author_id, sum(pageviews) as total_pageviews, avg(timespent) as average_timespent")
        )
            ->join('article_author', 'article_author.article_id', '=', 'article_aggregated_views.article_id')
            ->where('timespent', '<=', self::TIMESPENT_IGNORE_THRESHOLD_SECS)
            ->whereRaw("$groupParameter <> ''")
            ->groupBy([$groupParameter, 'author_id'])
            // Conditions to select members of particular author segment
            // are empirically defined.
            // TODO Improve/describe this after further analysis is done
            ->havingRaw('avg(timespent) >= ?', ['120'])
            ->cursor();

        $segments = [];

        foreach ($queryItems as $item) {
            if ($totalPageviews[$item->$groupParameter] === 0) {
                continue;
            }
            $ratio = (int) $item->total_pageviews / $totalPageviews[$item->$groupParameter];
            // Conditions to select members of particular author segment
            // are empirically defined.
            // TODO Improve/describe this after further analysis is done
            if ($ratio >= 0.25 && $item->total_pageviews >= 5) {
                if (!array_key_exists($item->author_id, $segments)) {
                    $segments[$item->author_id] = [];
                }
                $segments[$item->author_id][] = $item->$groupParameter;
            }
        }

        return $segments;
    }
}
