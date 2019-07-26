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

    public function articlesConcurrentsCount(Request $request)
    {
        $ids = (array) $request->input('external_id', []);

        if (!$ids) {
            $urls = (array) $request->input('url', []);
            if ($urls) {
                $articles = Article::whereIn('url', $urls)->get();
                $ids = $articles->pluck('external_id')->toArray();
            }
        }

        if (!$ids) {
            abort(400, 'Please specify external_id or url parameters');
        }

        $records = $this->journalHelpers->currentConcurrentsCount(function (ConcurrentsRequest $r) use ($ids) {
            $r->addGroup('article_id');
            if (count($ids) > 0) {
                $r->addFilter('article_id', ...$ids);
            }
        });

        $articles = [];
        foreach ($records as $record) {
            $articles[] = [
                'external_id' => $record->tags->article_id,
                'count' => $record->count,
            ];
        }

        return response()->json([
            'articles' => $articles,
        ]);
    }

    public function concurrentsCount()
    {
        $records = $this->journalHelpers->currentConcurrentsCount();
        return response()->json([
            'total' => $records->sum('count'),
        ]);
    }
}
