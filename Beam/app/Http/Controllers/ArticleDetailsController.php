<?php

namespace App\Http\Controllers;

use App\Article;
use App\Contracts\JournalAggregateRequest;
use App\Contracts\JournalContract;
use App\Http\Resources\ArticleResource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ArticleDetailsController extends Controller
{
    private $journal;

    public function __construct(JournalContract $journal)
    {

        $this->journal = $journal;
    }

    public function show(Article $article, Request $request)
    {
        $timeBefore = Carbon::now();
        $timeAfter = $article->published_at;

        $uniqueRequest = new JournalAggregateRequest('pageviews', 'browsers');
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

        return response()->format([
            'html' => view('articles.show', [
                'article' => $article,
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
