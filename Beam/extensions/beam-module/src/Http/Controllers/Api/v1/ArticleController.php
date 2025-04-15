<?php

namespace Remp\BeamModule\Http\Controllers\Api\v1;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Remp\BeamModule\Helpers\Misc;
use Remp\BeamModule\Http\Requests\Api\v1\ArticleUpsertRequest;
use Remp\BeamModule\Http\Requests\Api\v1\ReadArticlesRequest;
use Remp\BeamModule\Http\Requests\Api\v1\TopArticlesSearchRequest;
use Remp\BeamModule\Http\Requests\Api\v1\UnreadArticlesRequest;
use Remp\BeamModule\Http\Resources\ArticleResource;
use Remp\BeamModule\Model\Article;
use Remp\BeamModule\Model\Author;
use Remp\BeamModule\Model\Newsletter\NewsletterCriterionEnum;
use Remp\BeamModule\Model\NewsletterCriterion;
use Remp\BeamModule\Model\Pageviews\Api\v1\TopSearch;
use Remp\BeamModule\Model\Section;
use Remp\BeamModule\Model\Tag;
use Remp\Journal\AggregateRequest;
use Remp\Journal\JournalContract;
use Remp\Journal\ListRequest;

class ArticleController
{
    public function topArticles(TopArticlesSearchRequest $request, TopSearch $topSearch)
    {
        return response()->json($topSearch->topArticles($request));
    }

    public function upsert(ArticleUpsertRequest $request)
    {
        Log::info('Upserting articles', ['params' => $request->json()->all()]);

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
            foreach ($a['sections'] ?? [] as $sectionName) {
                $section = Section::firstOrCreate([
                    'name' => $sectionName,
                ]);
                $article->sections()->attach($section);
            }

            $article->tags()->detach();
            foreach ($a['tags'] ?? [] as $tagName) {
                $tag = Tag::firstOrCreate([
                    'name' => $tagName,
                ]);
                $article->tags()->attach($tag);
            }

            $article->authors()->detach();
            foreach ($a['authors'] as $authorName) {
                $section = Author::firstOrCreate([
                    'name' => $authorName,
                ]);
                $article->authors()->attach($section);
            }

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
            $newTitles = $a['titles'] ?? [];

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

            $article->load(['authors', 'sections', 'tags']);
            $articles[] = $article;
        }

        return response()->format([
            'html' => redirect(route('articles.pageviews'))->with('success', 'Article created'),
            'json' => ArticleResource::collection(collect($articles)),
        ]);
    }

    public function unreadArticlesForUsers(UnreadArticlesRequest $request, JournalContract $journal)
    {
        // Request with timespan 30 days typically takes about 50 seconds,
        // therefore add some safe margin to request execution time
        set_time_limit(120);

        $articlesCount = $request->input('articles_count');
        $timespan = $request->input('timespan');
        $readArticlesTimespan = $request->input('read_articles_timespan');

        $ignoreAuthors = $request->input('ignore_authors', []);
        $ignoreContentTypes = $request->input('ignore_content_types', []);

        $topArticlesPerCriterion = [];

        /** @var NewsletterCriterion[] $criteria */
        $criteria = [];
        foreach ($request->input('criteria') as $criteriaString) {
            $criteria[] = new NewsletterCriterion(NewsletterCriterionEnum::from($criteriaString));
            $topArticlesPerCriterion[] = null;
        }
        $topArticlesPerUser = [];

        $timeAfter = Misc::timespanInPast($timespan);
        // If no read_articles_timespan is specified, check for week old read articles (past given timespan)
        $readArticlesAfter = $readArticlesTimespan ? Misc::timespanInPast($readArticlesTimespan) : (clone $timeAfter)->subWeek();
        $timeBefore = Carbon::now();

        foreach (array_chunk($request->user_ids, 100) as $userIdsChunk) {
            $usersReadArticles = $this->readArticlesForUsers($journal, $readArticlesAfter, $timeBefore, $userIdsChunk);

            // Save top articles per user
            foreach ($userIdsChunk as $userId) {
                $topArticlesUrls = [];
                $topArticlesUrlsOrdered = [];

                $i = 0;
                $criterionIndex = 0;
                while (count($topArticlesUrls) < $articlesCount) {
                    if (!$topArticlesPerCriterion[$criterionIndex]) {
                        $criterion = $criteria[$criterionIndex];
                        $topArticlesPerCriterion[$criterionIndex] = $criterion->getCachedArticles($timespan, $ignoreAuthors, $ignoreContentTypes);
                    }

                    if ($i >= count($topArticlesPerCriterion[$criterionIndex])) {
                        if ($criterionIndex === count($criteria) - 1) {
                            break;
                        }
                        $criterionIndex++;
                        $i = 0;
                        continue;
                    }

                    $topArticle = $topArticlesPerCriterion[$criterionIndex][$i];
                    if ((!array_key_exists($userId, $usersReadArticles) || !array_key_exists($topArticle->external_id, $usersReadArticles[$userId]))
                        && !array_key_exists($topArticle->url, $topArticlesUrls)) {
                        $topArticlesUrls[$topArticle->url] = true;
                        $topArticlesUrlsOrdered[] = $topArticle->url;
                    }

                    $i++;
                }

                $topArticlesPerUser[$userId] = $topArticlesUrlsOrdered;
            }
        }

        return response()->json([
            'status' => 'ok',
            'data' => $topArticlesPerUser
        ]);
    }

    private function readArticlesForUsers(JournalContract $journal, Carbon $timeAfter, Carbon $timeBefore, array $userIds): array
    {
        $usersReadArticles = [];
        $r = new AggregateRequest('pageviews', 'load');
        $r->setTimeAfter($timeAfter);
        $r->setTimeBefore($timeBefore);
        $r->addGroup('user_id', 'article_id');
        $r->addFilter('user_id', ...$userIds);

        $result = collect($journal->count($r));
        foreach ($result as $item) {
            if ($item->tags->article_id !== '') {
                $userId = $item->tags->user_id;
                if (!array_key_exists($userId, $usersReadArticles)) {
                    $usersReadArticles[$userId] = [];
                }
                $usersReadArticles[$userId][$item->tags->article_id] = true;
            }
        }
        return $usersReadArticles;
    }

    public function readArticles(ReadArticlesRequest $request, JournalContract $journal)
    {
        $from = $request->input('from');
        $to = $request->input('to');
        $userId = $request->input('user_id');
        $browserId = $request->input('browser_id');

        $r = new ListRequest('pageviews');
        $r->addFilter('action', 'load');
        $r->addGroup('user_id', 'browser_id', 'article_id', 'time');
        if ($from) {
            $r->setTimeAfter(Carbon::parse($from));
        }
        if ($to) {
            $r->setTimeBefore(Carbon::parse($to));
        }
        if ($userId) {
            $r->addFilter('user_id', $userId);
        }
        if ($browserId) {
            $r->addFilter('browser_id', $browserId);
        }

        $articles = [];
        $result = $journal->list($r);
        foreach ($result as $item) {
            if ($item->tags->article_id !== '') {
                $articleId = $item->tags->article_id;
                if (!array_key_exists($articleId, $articles)) {
                    $articles[$articleId] = $item->tags;
                } else if (strtotime($articles[$articleId]->time) < strtotime($item->tags->time)) {
                    $articles[$articleId] = $item->tags;
                }
            }
        }

        // sort by time
        uasort($articles, fn($a, $b) => strtotime($b->time) <=> strtotime($a->time));

        return response()->json(array_values($articles));
    }
}
