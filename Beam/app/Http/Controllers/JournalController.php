<?php

namespace App\Http\Controllers;

use App\Article;
use App\Helpers\Journal\JournalHelpers;
use Illuminate\Http\Request;
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

    public function concurrentsCount(Request $request, $externalArticleId = null)
    {
        $url = $request->input('url');

        if (!$externalArticleId && $url) {
            $article = Article::where('url', $url)->first();
            if (isset($article->external_id)) {
                $externalArticleId = $article->external_id;
            }
        }

        return response()->json([
            'count' => $this->journalHelpers->concurrentsCount($externalArticleId),
        ]);
    }
}
