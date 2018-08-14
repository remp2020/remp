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
