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

            $sectionIds = [];
            foreach ($a['sections'] ?? [] as $section) {
                $sectionIds[] = $this->upsertSection($section)->id;
            }
            $article->sections()->sync($sectionIds);

            $tagIds = [];
            foreach ($a['tags'] ?? [] as $tag) {
                $tagObj = $this->upsertTag($tag);
                $tagIds[] = $tagObj->id;

                $categoryIds = [];
                foreach ($tag['categories'] ?? [] as $tagCategory) {
                    $categoryIds[] = $this->upsertTagCategory($tagCategory)->id;
                }
                $tagObj->tagCategories()->sync($categoryIds);
            }
            $article->tags()->sync($tagIds);

            $authorIds = [];
            foreach ($a['authors'] ?? [] as $author) {
                $authorIds[] = $this->upsertAuthor($author)->id;
            }
            $article->authors()->sync($authorIds);

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

    private function upsertSection(array $section): Section
    {
        $model = Section::where('external_id', $section['external_id'])->first();
        $model ??= Section::where('name', $section['name'])->whereNull('external_id')->first();

        if ($model) {
            $model->update($section);
            return $model;
        }

        return Section::create($section);
    }

    private function upsertTag(array $tag): Tag
    {
        $model = Tag::where('external_id', $tag['external_id'])->first();
        $model ??= Tag::where('name', $tag['name'])->whereNull('external_id')->first();

        if ($model) {
            $model->update($tag);
            return $model;
        }

        return Tag::create([
            'name' => $tag['name'],
            'external_id' => $tag['external_id'],
        ]);
    }

    private function upsertTagCategory(array $tagCategory): TagCategory
    {
        $model = TagCategory::where('external_id', $tagCategory['external_id'])->first();
        $model ??= TagCategory::where('name', $tagCategory['name'])->whereNull('external_id')->first();

        if ($model) {
            $model->update($tagCategory);
            return $model;
        }

        return TagCategory::create($tagCategory);
    }

    private function upsertAuthor(array $author): Author
    {
        $model = Author::where('external_id', $author['external_id'])->first();
        $model ??= Author::where('name', $author['name'])->whereNull('external_id')->first();

        if ($model) {
            $model->update($author);
            return $model;
        }

        return Author::create($author);
    }
}
