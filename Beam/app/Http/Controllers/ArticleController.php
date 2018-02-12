<?php

namespace App\Http\Controllers;

use App\Article;
use App\Http\Requests\ArticleRequest;
use App\Http\Resources\ArticleResource;
use HTML;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;

class ArticleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->format([
            'html' => view('articles.index'),
            'json' => ArticleResource::collection(Article::paginate()),
        ]);
    }

    public function json(Request $request, Datatables $datatables)
    {
        $articles = Article::select()
            ->with('property')
            ->get();

        return $datatables->of($articles)
            ->addColumn('title', function (Article $article) {
                return HTML::linkRoute('articles.show', $article->title, $article);
            })
            ->addColumn('url', function (Article $article) {
                return HTML::link($article->url, $article->url);
            })
            ->addColumn('image_url', function (Article $article) {
                if (!$article->image_url) {
                    return "";
                }
                return HTML::image($article->image_url, $article->title, ['style' => 'height: 80px;']);
            })
            ->make(true);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param ArticleRequest|Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(ArticleRequest $request)
    {
        $article = new Article();
        $article->fill($request->all());
        $article->save();

        return response()->format([
            'html' => redirect(route('articles.index'))->with('success', 'Article created'),
            'json' => new ArticleResource($article),
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Article  $article
     * @return \Illuminate\Http\Response
     */
    public function show(Article $article)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Article  $article
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Article $article)
    {
        $this->validate($request, [
            'name' => 'bail|required|unique:articles|max:255',
        ]);

        $article->fill($request->all());
        $article->save();

        return redirect(route('articles.index'))->with('success', 'Article updated');
    }
}
