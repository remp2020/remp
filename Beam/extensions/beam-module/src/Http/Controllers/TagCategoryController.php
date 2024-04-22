<?php

namespace Remp\BeamModule\Http\Controllers;

use Remp\BeamModule\Model\Article;
use Remp\BeamModule\Model\ArticlesDataTable;
use Remp\BeamModule\Model\Author;
use Remp\BeamModule\Model\Tag;
use Remp\BeamModule\Model\Section;
use Remp\BeamModule\Http\Resources\TagCategoryResource;
use Remp\BeamModule\Model\TagCategoriesDataTable;
use Remp\BeamModule\Model\TagCategory;
use Yajra\DataTables\DataTables;
use Illuminate\Http\Request;
use Html;
use Remp\BeamModule\Model\TagsDataTable;

class TagCategoryController extends Controller
{
    public function index(Request $request)
    {
        return response()->format([
            'html' => view('beam::tagcategories.index', [
                'tagCategories' => TagCategory::query()->pluck('name', 'id'),
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
            'json' => TagCategoryResource::collection(TagCategory::paginate()),
        ]);
    }

    public function show(TagCategory $tagCategory, Request $request)
    {
        return response()->format([
            'html' => view('beam::tagcategories.show', [
                'tagCategory' => $tagCategory,
                'tags' => Tag::query()->pluck('name', 'id'),
                'contentTypes' => Article::groupBy('content_type')->pluck('content_type', 'content_type'),
                'sections' => Section::query()->pluck('name', 'id'),
                'authors' => Author::query()->pluck('name', 'id'),
                'publishedFrom' => $request->input('published_from', 'today - 30 days'),
                'publishedTo' => $request->input('published_to', 'now'),
                'conversionFrom' => $request->input('conversion_from', 'today - 30 days'),
                'conversionTo' => $request->input('conversion_to', 'now'),
            ]),
            'json' => new TagCategoryResource($tagCategory),
        ]);
    }

    public function dtTagCategories(Request $request, DataTables $datatables, TagCategoriesDataTable $tagCategoriesDataTable)
    {
        return $tagCategoriesDataTable->getDataTable($request, $datatables);
    }

    public function dtTags(TagCategory $tagCategory, Request $request, DataTables $datatables, TagsDataTable $tagsDataTable)
    {
        $tagsDataTable->setTagCategory($tagCategory);
        return $tagsDataTable->getDataTable($request, $datatables);
    }

    public function dtArticles(TagCategory $tagCategory, Request $request, DataTables $datatables, ArticlesDataTable $articlesDataTable)
    {
        $articlesDataTable->setTagCategory($tagCategory);
        return $articlesDataTable->getDataTable($request, $datatables);
    }
}
