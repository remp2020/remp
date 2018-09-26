<?php

namespace App\Http\Controllers;

use App\Article;
use App\Contracts\JournalAggregateRequest;
use App\Contracts\JournalContract;
use App\Http\Resources\ArticleResource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ArticleDetailsController extends Controller
{
    private $journal;

    public function __construct(JournalContract $journal)
    {
        $this->journal = $journal;
    }

    private function getIntervalDependingOnArticlePublishedDate(Article $article): array
    {
        $articleAgeInMins = Carbon::now()->diffInMinutes($article->published_at);
        if ($articleAgeInMins <= 60) { // 1 hour
            return ["1m", 1];
        } else if ($articleAgeInMins <= 60*24) { // 1 day
            return ["20m", 20];
        } else if ($articleAgeInMins <= 7*60*24) { // 7 days
            return ["1h", 60];
        } else if ($articleAgeInMins <= 30*60*24) { // 30 days
            return ["2h", 120];
        } else if ($articleAgeInMins <= 90*60*24) { // 90 days
            return ["3h", 180];
        } else if ($articleAgeInMins <= 180*60*24) { // 180 days
            return ["4h", 240];
        } else if ($articleAgeInMins <= 365*60*24) { // 1 year
            return ["6h", 360];
        } else { // 1+ year
            return ["8h", 480];
        }
    }

    private function getElasticTimeIterator(Carbon $timeAfter, int $intervalMinutes): Carbon
    {
        // iterator has to be the earliest start of the interval (of $intervalMinutes) that includes $timeAfter
        $timeIterator = (clone $timeAfter)->startOfDay();
        while ($timeIterator->lessThanOrEqualTo($timeAfter)) {
            $timeIterator->addMinutes($intervalMinutes);
        }
        return $timeIterator->subMinutes($intervalMinutes);
    }

    public function timeHistogram(Article $article, Request $request)
    {
        $request->validate([
            'tz' => 'timezone',
            'interval' => 'required|in:today,7days,30days,all',
        ]);

        $tz = new \DateTimeZone($request->get('tz', 'UTC'));

        $interval = $request->get('interval');
        switch ($interval) {
            case 'today':
                $timeBefore = Carbon::now($tz);
                $timeAfter = Carbon::today($tz);
                $intervalElastic = '20m';
                $intervalMinutes = 20;
                break;
            case '7days':
                $timeBefore = Carbon::now($tz);
                $timeAfter = Carbon::today($tz)->subDays(6);
                $intervalElastic = '1h';
                $intervalMinutes = 60;
                break;
            case '30days':
                $timeBefore = Carbon::now($tz);
                $timeAfter = Carbon::today($tz)->subDays(29);
                $intervalElastic = '2h';
                $intervalMinutes = 120;
                break;
            case 'all':
                $timeBefore = Carbon::now($tz);
                $timeAfter = (clone $article->published_at)->tz($tz);
                [$intervalElastic, $intervalMinutes] = $this->getIntervalDependingOnArticlePublishedDate($article);
                break;
            default:
                throw new InvalidArgumentException("Parameter 'interval' must be one of the [today,7days,30days] values, instead '$interval' provided");
        }

        $journalRequest = new JournalAggregateRequest('pageviews', 'load');
        $journalRequest->addFilter('article_id', $article->external_id);
        $journalRequest->setTimeAfter($timeAfter);
        $journalRequest->setTimeBefore($timeBefore);
        $journalRequest->setTimeHistogram($intervalElastic, '0h');
        $journalRequest->addGroup('derived_referer_medium');
        $currentRecords = $this->journal->count($journalRequest);

        // Get all tags
        $tags = [];
        foreach ($currentRecords as $records) {
            $tags[] = $records->tags->derived_referer_medium;
        }

        // Values might be missing in time histogram, therefore fill all tags with 0s by default
        $results = [];
        $timeIterator = $this->getElasticTimeIterator($timeAfter, $intervalMinutes);
        while ($timeIterator->lessThan($timeBefore)) {
            $zuluDate = $timeIterator->toIso8601ZuluString();
            $results[$zuluDate] = collect($tags)->mapWithKeys(function ($item) {
                return [$item => 0];
            });
            $results[$zuluDate]['Date'] = $zuluDate;

            $timeIterator->addMinutes($intervalMinutes);
        }

        // Save results
        foreach ($currentRecords as $records) {
            if (!isset($records->time_histogram)) {
                continue;
            }
            $currentTag = $records->tags->derived_referer_medium;

            foreach ($records->time_histogram as $timeValue) {
                // take 4 and less as 0 (Elastic might return approximate results)
                $results[$timeValue->time][$currentTag] = $timeValue->value < 5 ? 0 : $timeValue->value;
            }
        }
        $results = array_values($results);

        return response()->json([
            'published_at' => $article->published_at->toIso8601ZuluString(),
            'interval_minutes' => $intervalMinutes,
            'results' => $results,
            'tags' => $tags
        ]);
    }

    public function show(Article $article, Request $request)
    {
        $timeBefore = Carbon::now();
        $timeAfter = $article->published_at;

        $uniqueRequest = new JournalAggregateRequest('pageviews', 'load');
        $uniqueRequest->setTimeAfter($timeAfter);
        $uniqueRequest->setTimeBefore($timeBefore);
        $uniqueRequest->addGroup('article_id');
        $uniqueRequest->addFilter('article_id', $article->external_id);
        $results = $this->journal->unique($uniqueRequest);
        $uniqueBrowsersCount = $results[0]->count;

        $conversionRate = ($article->conversions()->count() / $uniqueBrowsersCount) * 100;

        $newSubscriptionsCountSql = <<<SQL
        select count(*) as subscriptions_count from (
            select c1.* from conversions c1
            left join conversions c2
            on c1.user_id = c2.user_id and c2.paid_at < c1.paid_at
            where c2.id is Null
            and c1.article_id = ?
        ) t
SQL;
        $newSubscriptionsCount = DB::select($newSubscriptionsCountSql, [$article->id])[0]->subscriptions_count;

        $renewSubscriptionsCountSql = <<<SQL
        select count(*) as subscriptions_count from (
            select c1.user_id from conversions c1
            left join conversions c2
            on c1.user_id = c2.user_id and c2.paid_at < c1.paid_at and c2.id != c1.id
            where c2.id is not Null
            and c1.article_id = ?
            group by user_id
        ) t
SQL;
        $renewSubscriptionsCount = DB::select($renewSubscriptionsCountSql, [$article->id])[0]->subscriptions_count;

        $pageviewsSubscribersToAllRatio = ($article->pageviews_subscribers / $article->pageviews_all) * 100;

        return response()->format([
            'html' => view('articles.show', [
                'article' => $article,
                'pageviewsSubscribersToAllRatio' => $pageviewsSubscribersToAllRatio,
                'conversionRate' => $conversionRate,
                'uniqueBrowsersCount' => $uniqueBrowsersCount,
                'newSubscriptionsCount' => $newSubscriptionsCount,
                'renewSubscriptionsCount' => $renewSubscriptionsCount,
                'dataFrom' => $request->input('data_from', 'now - 30 days'),
                'dataTo' => $request->input('data_to', 'now'),
            ]),
            'json' => new ArticleResource($article)
        ]);
    }
}
