<?php

namespace Remp\BeamModule\Http\Controllers;

use Remp\BeamModule\Model\Article;
use Remp\BeamModule\Model\ArticlesDataTable;
use Remp\BeamModule\Model\Author;
use Illuminate\Http\Request;
use Remp\BeamModule\Http\Resources\TagResource;
use Remp\BeamModule\Model\Tag;
use Remp\BeamModule\Model\Section;
use Remp\BeamModule\Model\TagsDataTable;
use Yajra\DataTables\DataTables;
use Html;

class TagController extends Controller
{
    public function index(Request $request)
    {
        return response()->format([
            'html' => view('beam::tags.index', [
                'tags' => Tag::query()->pluck('name', 'id'),
                'contentTypes' => array_merge(
                    ['all'],
                    Article::groupBy('content_type')->pluck('content_type')->toArray()
                ),
                'publishedFrom' => $request->input('published_from', 'today - 30 days'),
                'publishedTo' => $request->input('published_to', 'now'),
                'conversionFrom' => $request->input('conversion_from', 'today - 30 days'),
                'conversionTo' => $request->input('conversion_to', 'now'),
                'contentType' => $request->input('content_type', 'all'),
            ]),
            'json' => TagResource::collection(Tag::paginate($request->get('per_page', 15)))->preserveQuery(),
        ]);
    }

    public function show(Tag $tag, Request $request)
    {
        return response()->format([
            'html' => view('beam::tags.show', [
                'tag' => $tag,
                'tags' => Tag::all(['name', 'id'])->pluck('name', 'id'),
                'contentTypes' => Article::groupBy('content_type')->pluck('content_type', 'content_type'),
                'sections' => Section::all(['name', 'id'])->pluck('name', 'id'),
                'authors' => Author::all(['name', 'id'])->pluck('name', 'id'),
                'publishedFrom' => $request->input('published_from', 'today - 30 days'),
                'publishedTo' => $request->input('published_to', 'now'),
                'conversionFrom' => $request->input('conversion_from', 'today - 30 days'),
                'conversionTo' => $request->input('conversion_to', 'now'),
            ]),
            'json' => new TagResource($tag),
        ]);
    }

    public function dtTags(Request $request, DataTables $datatables, TagsDataTable $tagsDataTable)
    {
        return $tagsDataTable->getDataTable($request, $datatables);
    }

    public function dtArticles(Tag $tag, Request $request, Datatables $datatables, ArticlesDataTable $articlesDataTable)
    {
        $articlesDataTable->setTag($tag);
        return $articlesDataTable->getDataTable($request, $datatables);
    }
}
