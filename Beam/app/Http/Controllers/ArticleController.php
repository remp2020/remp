<?php

namespace App\Http\Controllers;

use App\Article;
use App\Author;
use App\Http\Requests\ArticleRequest;
use App\Http\Resources\ArticleResource;
use App\Section;
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
            ->with(['authors', 'sections']);

        return $datatables->of($articles)
            ->addColumn('title', function (Article $article) {
                return HTML::link($article->url, $article->title);
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

        foreach ($request->get('sections', []) as $sectionName) {
            $section = Section::firstOrCreate([
                'name' => $sectionName,
            ]);
            $article->sections()->attach($section);
        }

        foreach ($request->get('authors', []) as $authorName) {
            $section = Author::firstOrCreate([
                'name' => $authorName,
            ]);
            $article->authors()->attach($section);
        }

        $article->load(['authors', 'sections']);

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
     * @param ArticleRequest|Request $request
     * @param  \App\Article $article
     *
     * @return \Illuminate\Http\Response
     */
    public function update(ArticleRequest $request, Article $article)
    {
        $article->fill($request->all());
        $article->save();

        $article->sections()->detach();
        foreach ($request->get('sections', []) as $sectionName) {
            $section = Section::firstOrCreate([
                'name' => $sectionName,
            ]);
            $article->sections()->attach($section);
        }

        $article->authors()->detach();
        foreach ($request->get('authors', []) as $authorName) {
            $section = Author::firstOrCreate([
                'name' => $authorName,
            ]);
            $article->authors()->attach($section);
        }

        $article->load(['authors', 'sections']);

        return response()->format([
            'html' => redirect(route('articles.index'))->with('success', 'Article created'),
            'json' => new ArticleResource($article),
        ]);
    }
}
