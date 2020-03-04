<?php

namespace App\Http\Controllers;

use App\Article;
use App\Author;
use App\Helpers\Journal\JournalHelpers;
use App\Helpers\Misc;
use App\Http\Requests\ArticleRequest;
use App\Http\Requests\ArticleUpsertRequest;
use App\Http\Requests\UnreadArticlesRequest;
use App\Http\Resources\ArticleResource;
use App\Model\Config\ConversionRateConfig;
use App\Model\NewsletterCriterion;
use App\Model\Tag;
use App\Section;
use Html;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Remp\Journal\AggregateRequest;
use Remp\Journal\JournalContract;
use Remp\Journal\TokenProvider;
use Yajra\Datatables\Datatables;
use Yajra\DataTables\EloquentDataTable;

class ArticleController extends Controller
{
    private $journal;

    private $journalHelper;

    private $conversionRateConfig;

    private $tokenProvider;

    public function __construct(JournalContract $journal, ConversionRateConfig $conversionRateConfig, TokenProvider $tokenProvider)
    {
        $this->journal = $journal;
        $this->journalHelper = new JournalHelpers($journal);
        $this->conversionRateConfig = $conversionRateConfig;
        $this->tokenProvider = $tokenProvider;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->format([
            'html' => view('articles.pageviews', [
                'authors' => Author::all()->pluck('name', 'id'),
                'sections' => Section::all()->pluck('name', 'id'),
            ]),
            'json' => ArticleResource::collection(Article::paginate()),
        ]);
    }

    public function conversions(Request $request)
    {
        return response()->format([
            'html' => view('articles.conversions', [
                'authors' => Author::all()->pluck('name', 'id'),
                'sections' => Section::all()->pluck('name', 'id'),
                'tags' => Tag::all()->pluck('name', 'id'),
                'publishedFrom' => $request->input('published_from', 'now - 30 days'),
                'publishedTo' => $request->input('published_to', 'now'),
                'conversionFrom' => $request->input('conversion_from', 'now - 30 days'),
                'conversionTo' => $request->input('conversion_to', 'now'),
            ]),
            'json' => ArticleResource::collection(Article::paginate()),
        ]);
    }

    public function dtConversions(Request $request, Datatables $datatables)
    {
        $articlesQuery = Article::query()->selectRaw(implode(',', [
                "articles.id",
                "articles.external_id",
                "articles.title",
                "articles.url",
                "articles.published_at",
                "count(conversions.id) as conversions_count",
                "coalesce(sum(conversions.amount), 0) as conversions_sum",
                "avg(conversions.amount) as conversions_avg"
            ]))
            ->with(['authors', 'sections', 'tags'])
            ->leftJoin('conversions', 'articles.id', '=', 'conversions.article_id')
            ->groupBy(['articles.id', 'articles.title', 'articles.url', 'articles.published_at']);

        if ($request->input('published_from')) {
            $publishedFrom = Carbon::parse($request->input('published_from'), $request->input('tz'))->tz('UTC');
            $articlesQuery->where('published_at', '>=', $publishedFrom);
        }
        if ($request->input('published_to')) {
            $publishedTo = Carbon::parse($request->input('published_to'), $request->input('tz'))->tz('UTC');
            $articlesQuery->where('published_at', '<=', $publishedTo);
        }
        if ($request->input('conversion_from')) {
            $conversionFrom = Carbon::parse($request->input('conversion_from'), $request->input('tz'))->tz('UTC');
            $articlesQuery->where('paid_at', '>=', $conversionFrom);
        }
        if ($request->input('conversion_to')) {
            $conversionTo = Carbon::parse($request->input('conversion_to'), $request->input('tz'))->tz('UTC');
            $articlesQuery->where('paid_at', '<=', $conversionTo);
        }
        $token = $this->tokenProvider->getToken();
        if ($token) {
            $articlesQuery->where('property_uuid', '=', $token);
        }

        $articles = $articlesQuery->get();

        $conversionsQuery = \DB::table('conversions')
            ->select([
                DB::raw('count(*) as count'),
                DB::raw('sum(amount) as sum'),
                DB::raw('avg(amount) as avg'),
                'currency',
                'article_id',
                'articles.external_id',
            ])
            ->join('articles', 'articles.id', '=', 'conversions.article_id')
            ->whereIn('article_id', (clone $articles)->pluck('id'))
            ->groupBy(['conversions.article_id', 'conversions.currency']);

        $externalIdsToUniqueBrowsersCount = $this->journalHelper->uniqueBrowsersCountForArticles($articles);

        $conversionSums = [];
        $conversionAverages = [];
        $conversionRates = collect();

        foreach ($conversionsQuery->get() as $record) {
            $conversionSums[$record->article_id][$record->currency] = $record->sum;
            $conversionAverages[$record->article_id][$record->currency] = $record->avg;
            if ($externalIdsToUniqueBrowsersCount->get($record->external_id, 0) === 0) {
                $conversionRates[$record->external_id] = 0;
            } else {
                $conversionRates[$record->external_id] = $record->count / $externalIdsToUniqueBrowsersCount->get($record->external_id);
            }
        }

        /** @var EloquentDataTable $dt */
        $dt =  $datatables->of($articlesQuery);

        return $dt
            ->addColumn('title', function (Article $article) {
                return Html::link(route('articles.show', ['article' => $article->id]), $article->title);
            })
            ->addColumn('conversions_rate', function (Article $article) use ($externalIdsToUniqueBrowsersCount) {
                $uniqueCount = $externalIdsToUniqueBrowsersCount->get($article->external_id, 0);
                $threeMonthsAgo = Carbon::now()->subMonths(3);

                if ($uniqueCount === 0 || $article->published_at->lt($threeMonthsAgo)) {
                    return '';
                }

                $conversionCount = $article->conversions_count;
                return Article::computeConversionRate($conversionCount, $uniqueCount, $this->conversionRateConfig);
            })
            ->addColumn('amount', function (Article $article) use ($conversionSums) {
                if (!isset($conversionSums[$article->id])) {
                    return [0];
                }
                $amounts = [];
                foreach ($conversionSums[$article->id] as $currency => $c) {
                    $c = round($c, 2);
                    $amounts[] = "{$c} {$currency}";
                }
                return $amounts ?? [0];
            })
            ->addColumn('average', function (Article $article) use ($conversionAverages) {
                if (!isset($conversionAverages[$article->id])) {
                    return [0];
                }
                $average = [];
                foreach ($conversionAverages[$article->id] as $currency => $c) {
                    $c = round($c, 2);
                    $average[] = "{$c} {$currency}";
                }
                return $average ?? [0];
            })
            ->addColumn('authors', function (Article $article) {
                $authors = $article->authors->map(function (Author $author) {
                    return ['link' => Html::linkRoute('authors.show', $author->name, [$author])];
                });
                return $authors->implode('link', '<br/>');
            })
            ->orderColumn('amount', 'conversions_sum $1')
            ->orderColumn('average', 'conversions_avg $1')
            ->orderColumn('conversions_rate', DB::raw("FIELD(articles.external_id,". $conversionRates->sort()->keys()->implode(",") .") $1, conversions_count $1"))
            ->filterColumn('title', function (Builder $query, $value) {
                $query->where('articles.title', 'like', '%' . $value . '%');
            })
            ->filterColumn('authors', function (Builder $query, $value) {
                $values = explode(",", $value);
                $filterQuery = \DB::table('articles')
                    ->join('article_author', 'articles.id', '=', 'article_author.article_id', 'left')
                    ->whereIn('article_author.author_id', $values);
                $articleIds = $filterQuery->pluck('articles.id')->toArray();
                $query->whereIn('articles.id', $articleIds);
            })
            ->filterColumn('sections[, ].name', function (Builder $query, $value) {
                $values = explode(",", $value);
                $filterQuery = \DB::table('articles')
                    ->join('article_section', 'articles.id', '=', 'article_section.article_id', 'left')
                    ->whereIn('article_section.section_id', $values);
                $articleIds = $filterQuery->pluck('articles.id')->toArray();
                $query->whereIn('articles.id', $articleIds);
            })
            ->filterColumn('tags[, ].name', function (Builder $query, $value) {
                $values = explode(",", $value);
                $filterQuery = \DB::table('articles')
                    ->join('article_tag', 'articles.id', '=', 'article_tag.article_id', 'left')
                    ->whereIn('article_tag.tag_id', $values);
                $articleIds = $filterQuery->pluck('articles.id')->toArray();
                $query->whereIn('articles.id', $articleIds);
            })
            ->rawColumns(['authors'])
            ->make();
    }

    public function pageviews(Request $request)
    {
        return response()->format([
            'html' => view('articles.pageviews', [
                'authors' => Author::all()->pluck('name', 'id'),
                'sections' => Section::all()->pluck('name', 'id'),
                'tags' => Tag::all()->pluck('name', 'id'),
                'publishedFrom' => $request->input('published_from', 'now - 30 days'),
                'publishedTo' => $request->input('published_to', 'now'),
            ]),
            'json' => ArticleResource::collection(Article::paginate()),
        ]);
    }

    public function dtPageviews(Request $request, Datatables $datatables)
    {
        $articles = Article::selectRaw('articles.*,' .
            'CASE pageviews_all WHEN 0 THEN 0 ELSE (pageviews_subscribers/pageviews_all)*100 END AS pageviews_subscribers_ratio')
            ->with(['authors', 'sections', 'tags']);

        if ($request->input('published_from')) {
            $articles->where('published_at', '>=', Carbon::parse($request->input('published_from'), $request->input('tz'))->tz('UTC'));
        }
        if ($request->input('published_to')) {
            $articles->where('published_at', '<=', Carbon::parse($request->input('published_to'), $request->input('tz'))->tz('UTC'));
        }
        $token = $this->tokenProvider->getToken();
        if ($token) {
            $articles->where('property_uuid', '=', $token);
        }

        return $datatables->of($articles)
            ->addColumn('title', function (Article $article) {
                return Html::link(route('articles.show', ['article' => $article->id]), $article->title);
            })
            ->addColumn('avg_sum_all', function (Article $article) {
                if (!$article->timespent_all || !$article->pageviews_all) {
                    return 0;
                }
                return round($article->timespent_all / $article->pageviews_all);
            })
            ->addColumn('avg_sum_signed_in', function (Article $article) {
                if (!$article->timespent_signed_in || !$article->pageviews_signed_in) {
                    return 0;
                }
                return round($article->timespent_signed_in / $article->pageviews_signed_in);
            })
            ->addColumn('avg_sum_subscribers', function (Article $article) {
                if (!$article->timespent_subscribers || !$article->pageviews_subscribers) {
                    return 0;
                }
                return round($article->timespent_subscribers / $article->pageviews_subscribers);
            })
            ->addColumn('authors', function (Article $article) {
                $authors = $article->authors->map(function (Author $author) {
                    return ['link' => Html::linkRoute('authors.show', $author->name, [$author])];
                });
                return $authors->implode('link', '<br/>');
            })
            ->filterColumn('title', function (Builder $query, $value) {
                $query->where('articles.title', 'like', '%' . $value . '%');
            })
            ->filterColumn('authors', function (Builder $query, $value) {
                $values = explode(',', $value);
                $filterQuery = \DB::table('articles')
                    ->join('article_author', 'articles.id', '=', 'article_author.article_id', 'left')
                    ->whereIn('article_author.author_id', $values);
                $articleIds = $filterQuery->pluck('articles.id')->toArray();
                $query->whereIn('articles.id', $articleIds);
            })
            ->filterColumn('sections[, ].name', function (Builder $query, $value) {
                $values = explode(',', $value);
                $filterQuery = \DB::table('articles')
                    ->join('article_section', 'articles.id', '=', 'article_section.article_id', 'left')
                    ->whereIn('article_section.section_id', $values);
                $articleIds = $filterQuery->pluck('articles.id')->toArray();
                $query->whereIn('articles.id', $articleIds);
            })
            ->filterColumn('tags[, ].name', function (Builder $query, $value) {
                $values = explode(',', $value);
                $filterQuery = \DB::table('articles')
                    ->join('article_tag', 'articles.id', '=', 'article_tag.article_id', 'left')
                    ->whereIn('article_tag.tag_id', $values);
                $articleIds = $filterQuery->pluck('articles.id')->toArray();
                $query->whereIn('articles.id', $articleIds);
            })
            ->orderColumn('avg_sum_all', 'timespent_all / pageviews_all $1')
            ->orderColumn('avg_sum_signed_in', 'timespent_signed_in / pageviews_signed_in $1')
            ->orderColumn('avg_sum_subscribers', 'timespent_subscribers / pageviews_subscribers $1')
            ->rawColumns(['authors'])
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
        /** @var Article $article */
        $article = Article::firstOrNew([
            'external_id' => $request->get('external_id'),
        ]);
        $article->fill($request->all());
        $article->save();

        $article->sections()->detach();
        foreach ($request->get('sections', []) as $sectionName) {
            $section = Section::firstOrCreate([
                'name' => $sectionName,
            ]);
            $article->sections()->attach($section);
        }

        $article->tags()->detach();
        foreach ($request->get('tags', []) as $tagName) {
            $tag = Tag::firstOrCreate([
                'name' => $tagName,
            ]);
            $article->tags()->attach($tag);
        }

        $article->authors()->detach();
        foreach ($request->get('authors', []) as $authorName) {
            $section = Author::firstOrCreate([
                'name' => $authorName,
            ]);
            $article->authors()->attach($section);
        }

        $article->load(['authors', 'sections', 'tags']);

        return response()->format([
            'html' => redirect(route('articles.pageviews'))->with('success', 'Article created'),
            'json' => new ArticleResource($article),
        ]);
    }

    public function upsert(ArticleUpsertRequest $request)
    {
        Log::info('Upserting articles', ['params' => $request->json()->all()]);

        $articles = [];
        foreach ($request->get('articles', []) as $a) {
            // When saving to DB, Eloquent strips timezone information,
            // therefore convert to UTC
            $a['published_at'] = Carbon::parse($a['published_at'])->tz('UTC');
            $article = Article::upsert($a);

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

    public function unreadArticlesForUsers(UnreadArticlesRequest $request)
    {
        // Request with timespan 30 days typically takes about 50 seconds,
        // therefore add some safe margin to request execution time
        set_time_limit(120);

        $articlesCount = $request->input('articles_count');
        $timespan = $request->input('timespan');
        $readArticlesTimespan = $request->input('read_articles_timespan');

        $ignoreAuthors = $request->input('ignore_authors', []);

        $topArticlesPerCriterion = [];

        /** @var NewsletterCriterion[] $criteria */
        $criteria = [];
        foreach ($request->input('criteria') as $criteriaString) {
            $criteria[] = NewsletterCriterion::get($criteriaString);
            $topArticlesPerCriterion[] = null;
        }
        $topArticlesPerUser = [];

        $timeAfter = Misc::timespanInPast($timespan);
        // If no read_articles_timespan is specified, check for week old read articles (past given timespan)
        $readArticlesAfter = $readArticlesTimespan ? Misc::timespanInPast($readArticlesTimespan) : (clone $timeAfter)->subWeek();
        $timeBefore = Carbon::now();

        foreach (array_chunk($request->user_ids, 500) as $userIdsChunk) {
            $usersReadArticles = $this->readArticlesForUsers($readArticlesAfter, $timeBefore, $userIdsChunk);

            // Save top articles per user
            foreach ($userIdsChunk as $userId) {
                $topArticlesUrls = [];
                $topArticlesUrlsOrdered = [];

                $i = 0;
                $criterionIndex = 0;
                while (count($topArticlesUrls) < $articlesCount) {
                    if (!$topArticlesPerCriterion[$criterionIndex]) {
                        $criterion = $criteria[$criterionIndex];
                        $topArticlesPerCriterion[$criterionIndex] = $criterion->getCachedArticles($timespan, $ignoreAuthors);
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

    private function readArticlesForUsers(Carbon $timeAfter, Carbon $timeBefore, array $userIds): array
    {
        $usersReadArticles = [];
        $r = new AggregateRequest('pageviews', 'load');
        $r->setTimeAfter($timeAfter);
        $r->setTimeBefore($timeBefore);
        $r->addGroup('user_id', 'article_id');
        $r->addFilter('user_id', ...$userIds);

        $result = collect($this->journal->count($r));
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
}
