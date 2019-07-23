<?php

namespace App\Http\Controllers;

use App\Article;
use App\Helpers\Journal\JournalHelpers;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Remp\Journal\ConcurrentsRequest;
use Remp\Journal\JournalContract;

class JournalController extends Controller
{
    private $journal;

    private $journalHelpers;

    public function __construct(JournalContract $journalContract)
    {
        $this->journal = $journalContract;
        $this->journalHelpers = new JournalHelpers($journalContract);
    }

    public function flags()
    {
        return $this->journal->flags();
    }

    public function actions($group, $category)
    {
        return collect($this->journal->actions($group, $category));
    }

    public function concurrentsCount(Request $request)
    {
        $ids = (array) $request->input('id', []);

        if (!$ids) {
            $urls = (array) $request->input('url', []);
            if ($urls) {
                $articles = Article::whereIn('url', $urls)->get();
                foreach ($articles as $article) {
                    $ids[] = $article->external_id;
                }
            }
        }

        $timeBefore = Carbon::now();
        $timeAfter = (clone $timeBefore)->subSeconds(600); // Last 10 minutes

        $req = new ConcurrentsRequest();
        $req->setTimeAfter($timeAfter);
        $req->setTimeBefore($timeBefore);
        $req->addGroup('article_id');
        if ($ids > 0) {
            $req->addFilter('article_id', ...$ids);
        }
        $records = collect($this->journal->concurrents($req));

        $total = 0;
        $articles = [];
        foreach ($records as $record) {
            $total += $record->count;
            if ($ids) {
                $articles[] = [
                    'external_id' => $record->tags->article_id,
                    'count' => $record->count,
                ];
            }
        }

        $response = ['total' => $total];

        if ($articles) {
            $response = ['articles' => $articles];
        }

        return response()->json($response);
    }
}
