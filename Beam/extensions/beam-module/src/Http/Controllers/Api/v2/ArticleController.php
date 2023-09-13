<?php

namespace Remp\BeamModule\Http\Controllers\Api\v2;

use Remp\BeamModule\Model\Article;
use Remp\BeamModule\Model\Author;
use Remp\BeamModule\Http\Requests\Api\v2\ArticleUpsertRequest;
use Remp\BeamModule\Http\Requests\Api\v2\TopArticlesSearchRequest;
use Remp\BeamModule\Http\Resources\ArticleResource;
use Remp\BeamModule\Model\Pageviews\Api\v2\TopSearch;
use Remp\BeamModule\Model\Tag;
use Remp\BeamModule\Model\Section;
use Remp\BeamModule\Model\TagCategory;
use Illuminate\Support\Carbon;

class ArticleController
{
    public function topArticles(TopArticlesSearchRequest $request, TopSearch $topSearch)
    {
        return response()->json($topSearch->topArticles($request));
    }

    public function upsert(ArticleUpsertRequest $request)
    {
        $articles = [];
        foreach ($request->get('articles', []) as $a) {
            // When saving to DB, Eloquent strips timezone information,
            // therefore convert to UTC
            $a['published_at'] = Carbon::parse($a['published_at']);
            $a['content_type'] = $a['content_type'] ?? Article::DEFAULT_CONTENT_TYPE;
            $article = Article::updateOrCreate(
                ['external_id' => $a['external_id']],
                $a
            );

            $article->sections()->detach();
            foreach ($a['sections'] ?? [] as $section) {
                $sectionObj = $this->upsertSection($section);
                $article->sections()->attach($sectionObj);
            }

            $article->tags()->detach();
            foreach ($a['tags'] ?? [] as $tag) {
                $tagObj = $this->upsertTag($tag);
                $article->tags()->attach($tagObj);

                $tagObj->tagCategories()->detach();
                foreach ($tag['categories'] ?? [] as $tagCategory) {
                    $tagCategoryObj = $this->upsertTagCategory($tagCategory);
                    $tagObj->tagCategories()->attach($tagCategoryObj);
                }
            }

            $article->authors()->detach();
            foreach ($a['authors'] ?? [] as $author) {
                $authorObj = $this->upsertAuthor($author);
                $article->authors()->attach($authorObj);
            }

            if (isset($a['titles']) && is_array($a['titles'])) {
                // Load existing titles
                $existingArticleTitles = $article->articleTitles()
                    ->orderBy('updated_at')
                    ->get()
                    ->groupBy('variant');

                $lastTitles = [];
                foreach ($existingArticleTitles as $variant => $variantTitles) {
                    $lastTitles[$variant] = $variantTitles->last()->title;
                }

                // Saving titles
                $newTitles = $a['titles'];

                $newTitleVariants = array_keys($newTitles);
                $lastTitleVariants = array_keys($lastTitles);

                // Titles that were not present in new titles, but were previously recorded
                foreach (array_diff($lastTitleVariants, $newTitleVariants) as $variant) {
                    $lastTitle = $lastTitles[$variant];
                    if ($lastTitle !== null) {
                        // title was deleted and it was not recorded yet
                        $article->articleTitles()->create([
                            'variant' => $variant,
                            'title' => null // deleted flag
                        ]);
                    }
                }

                // New titles, not previously recorded
                foreach (array_diff($newTitleVariants, $lastTitleVariants) as $variant) {
                    $newTitle = html_entity_decode($newTitles[$variant], ENT_QUOTES);
                    $article->articleTitles()->create([
                        'variant' => $variant,
                        'title' => $newTitle
                    ]);
                }

                // Changed titles
                foreach (array_intersect($newTitleVariants, $lastTitleVariants) as $variant) {
                    $lastTitle = $lastTitles[$variant];
                    $newTitle = html_entity_decode($newTitles[$variant], ENT_QUOTES);

                    if ($lastTitle !== $newTitle) {
                        $article->articleTitles()->create([
                            'variant' => $variant,
                            'title' => $newTitle
                        ]);
                    }
                }
            }

            $article->load(['authors', 'sections', 'tags', 'tags.tagCategories']);
            $articles[] = $article;
        }

        return response()->format([
            'html' => redirect(route('articles.pageviews'))->with('success', 'Article created'),
            'json' => ArticleResource::collection(collect($articles)),
        ]);
    }

    private function upsertSection($section): Section
    {
        $sectionObj = Section::where('external_id', $section['external_id'])->first();
        if ($sectionObj) {
            $sectionObj->update($section);
            return $sectionObj;
        }

        $sectionObj = Section::where('name', $section['name'])->first();
        if ($sectionObj && $sectionObj->external_id === null) {
            $sectionObj->update($section);
            return $sectionObj;
        }

        return Section::firstOrCreate($section);
    }

    private function upsertTag($tag): Tag
    {
        $tagObj = Tag::where('external_id', $tag['external_id'])->first();
        if ($tagObj) {
            $tagObj->name = $tag['name'];
            $tagObj->save();
            return $tagObj;
        }

        $tagObj = Tag::where('name', $tag['name'])->first();
        if ($tagObj && $tagObj->external_id === null) {
            $tagObj->external_id = $tag['external_id'];
            $tagObj->save();
            return $tagObj;
        }

        return Tag::firstOrCreate([
            'name' => $tag['name'],
            'external_id' => $tag['external_id'],
        ]);
    }

    private function upsertTagCategory($tagCategory): TagCategory
    {
        $tagCategoryObj = TagCategory::where('external_id', $tagCategory['external_id'])->first();
        if ($tagCategoryObj) {
            $tagCategoryObj->update($tagCategory);
            return $tagCategoryObj;
        }

        $tagCategoryObj = TagCategory::where('name', $tagCategory['name'])->first();
        if ($tagCategoryObj && $tagCategoryObj->external_id === null) {
            $tagCategoryObj->update($tagCategory);
            return $tagCategoryObj;
        }

        return TagCategory::firstOrCreate($tagCategory);
    }

    private function upsertAuthor($author): Author
    {
        $authorObj = Author::where('external_id', $author['external_id'])->first();
        if ($authorObj) {
            $authorObj->update($author);
            return $authorObj;
        }

        $authorObj = Author::where('name', $author['name'])->first();
        if ($authorObj && $authorObj->external_id === null) {
            $authorObj->update($author);
            return $authorObj;
        }

        return Author::firstOrCreate($author);
    }
}
