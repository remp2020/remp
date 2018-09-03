<?php

namespace App\Http\Controllers;

use App\Article;
use App\Http\Resources\ArticleResource;
use Illuminate\Http\Request;

class ArticleDetailsController extends Controller
{
    public function show(Article $article, Request $request)
    {
        return response()->format([
            'html' => view('articles.show', [
                'article' => $article,
                'dataFrom' => $request->input('data_from', 'now - 30 days'),
                'dataTo' => $request->input('data_to', 'now'),
            ]),
            'json' => new ArticleResource($article)
        ]);
    }
}
