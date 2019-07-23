<?php

namespace App\Http\Controllers\Api;

use App\Article;
use App\Helpers\Journal\JournalHelpers;
use App\Http\Controllers\Controller;
use Remp\Journal\JournalContract;
use Illuminate\Http\Request;

class SegmentsController extends Controller
{
    private $journal;

    private $journalHelpers;

    public function __construct(JournalContract $journalContract)
    {
        $this->journal = $journalContract;
        $this->journalHelpers = new JournalHelpers($journalContract);
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
