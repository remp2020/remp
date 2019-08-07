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
        $externalIds = (array) $request->input('external_id', []);
        $urls = (array) $request->input('url', []);

        if (!$externalIds && !$urls) {
            abort(400, 'Please specify either external_id(s) or url(s) parameters');
        }

        $records = $this->journalHelpers->currentConcurrentsCount(function (ConcurrentsRequest $r) use ($externalIds, $urls) {
            if (count($externalIds) > 0) {
                $r->addFilter('article_id', ...$externalIds);
                $r->addGroup('article_id');
            }
            if (count($urls) > 0) {
                $r->addFilter('url', ...$urls);
                $r->addGroup('url');
            }
        });

        $articles = [];
        foreach ($records as $record) {
            $obj = [
                'count' => $record->count,
            ];

            if (isset($record->tags->article_id)) {
                $obj['external_id'] = $record->tags->article_id;
            }

            if (isset($record->tags->url)) {
                $obj['url'] = $record->tags->url;
            }

            $articles[] = $obj;
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
