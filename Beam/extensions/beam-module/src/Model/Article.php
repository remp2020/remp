<?php

namespace Remp\BeamModule\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Remp\BeamModule\Database\Factories\ArticleFactory;
use Remp\BeamModule\Helpers\Journal\JournalHelpers;
use Remp\BeamModule\Model\Config\ConversionRateConfig;
use Remp\Journal\AggregateRequest;
use Remp\Journal\JournalContract;
use Remp\Journal\TokenProvider;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;
use Yadakhov\InsertOnDuplicateKey;

class Article extends BaseModel implements Searchable
{
    use HasFactory;

    use InsertOnDuplicateKey;

    private const DEFAULT_TITLE_VARIANT = 'default';

    private const DEFAULT_IMAGE_VARIANT = 'default';

    public const DEFAULT_CONTENT_TYPE = 'article';

    private $journal;

    private $journalHelpers;

    private $cachedAttributes = [];

    private $conversionRateConfig;
    private int $conversionRateConfigLastLoadTimestamp = 0;

    protected $fillable = [
        'property_uuid',
        'external_id',
        'title',
        'author',
        'url',
        'content_type',
        'section',
        'image_url',
        'published_at',
        'pageviews_all',
        'pageviews_signed_in',
        'pageviews_subscribers',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function newFactory(): ArticleFactory
    {
        return ArticleFactory::new();
    }

    public function getSearchResult(): SearchResult
    {
        return new SearchResult($this, $this->title);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_uuid', 'uuid');
    }

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class);
    }

    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(Section::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function conversions(): HasMany
    {
        return $this->hasMany(Conversion::class);
    }

    public function conversionSources(): HasManyThrough
    {
        return $this->hasManyThrough(ConversionSource::class, Conversion::class);
    }

    public function pageviews(): HasMany
    {
        return $this->hasMany(ArticlePageviews::class);
    }

    public function timespent(): HasMany
    {
        return $this->hasMany(ArticleTimespent::class);
    }

    public function articleTitles(): HasMany
    {
        return $this->hasMany(ArticleTitle::class);
    }

    public function dashboardArticle(): HasOne
    {
        return $this->hasOne(DashboardArticle::class);
    }

    // Accessors

    /**
     * variants_count
     * indexed by title variant name first, image variant name second
     * @return array
     */
    public function getVariantsCountAttribute(): array
    {
        if (array_key_exists('variants_count', $this->cachedAttributes)) {
            return $this->cachedAttributes['variants_count'];
        }

        $r = (new AggregateRequest('pageviews', 'load'))
            ->setTime($this->published_at, Carbon::now())
            ->addGroup('article_id', 'title_variant', 'image_variant')
            ->addFilter('article_id', $this->external_id);

        $results = collect($this->getJournal()->unique($r));
        $titleVariants = [];
        $imageVariants = [];

        foreach ($results as $result) {
            if (!$result->count) {
                continue;
            }
            $titleVariant = $result->tags->title_variant ?? self::DEFAULT_TITLE_VARIANT;
            if (!isset($titleVariants[$titleVariant])) {
                $titleVariants[$titleVariant] = 0;
            }
            $imageVariant = $result->tags->image_variant ?? self::DEFAULT_IMAGE_VARIANT;
            if (!isset($imageVariants[$imageVariant])) {
                $imageVariants[$imageVariant] = 0;
            }

            $titleVariants[$titleVariant] += $result->count;
            $imageVariants[$imageVariant] += $result->count;
        }

        $this->cachedAttributes['variants_count'] = [
            'title' => $titleVariants,
            'image' => $imageVariants,
        ];

        return $this->cachedAttributes['variants_count'];
    }

    public function getTitleVariantsCountAttribute(): array
    {
        return $this->variants_count['title'];
    }

    public function getImageVariantsCountAttribute(): array
    {
        return $this->variants_count['image'];
    }

    /**
     * unique_browsers_count
     * @return int
     */
    public function getUniqueBrowsersCountAttribute(): int
    {
        if (array_key_exists('unique_browsers_count', $this->cachedAttributes)) {
            return $this->cachedAttributes['unique_browsers_count'];
        }

        $results = $this->getJournalHelpers()->uniqueBrowsersCountForArticles(collect([$this]));
        $count = $results[$this->external_id] ?? 0;
        $this->cachedAttributes['unique_browsers_count'] = $count;
        return $count;

        // TODO revert to code below to avoid two Journal API requests after https://gitlab.com/remp/remp/issues/484 is fixed
        //$total = 0;
        //$variantsCount = $this->variants_count; // Retrieved from accessor
        //foreach ($variantsCount as $title => $titleVariants) {
        //    foreach ($titleVariants as $image => $count) {
        //        $total += $count;
        //    }
        //}
        //return $total;
    }

    /**
     * conversion_rate
     *
     * Deprecated usage without passing ConversionRateConfig. For now when using without passing $conversionRateConfig,
     * it'll fallback to default config until next major release when we'll remove nullable type and
     * $conversionRateConfig become mandatory.
     *
     * @return string
     */
    public function getConversionRateAttribute(?ConversionRateConfig $conversionRateConfig = null): string
    {
        if ($conversionRateConfig === null) {
            $conversionRateConfig = $this->getConversionRateConfig();
            trigger_error('Usage of this method without $conversionRateConfig argument is deprecated.', E_USER_DEPRECATED);
        }

        return self::computeConversionRate(
            $this->conversions->count(),
            $this->unique_browsers_count,
            $conversionRateConfig
        );
    }

    /**
     * renewed_conversions_count
     * @return int
     */
    public function getRenewedConversionsCountAttribute(): int
    {
        $renewSubscriptionsCountSql = <<<SQL
        select count(*) as subscriptions_count from (
            select c1.user_id from conversions c1
            left join conversions c2
            on c1.user_id = c2.user_id and c2.paid_at < c1.paid_at and c2.id != c1.id
            where c2.id is not Null
            and c1.article_id = ?
            group by user_id
        ) t
SQL;
        return DB::select($renewSubscriptionsCountSql, [$this->id])[0]->subscriptions_count;
    }

    /**
     * new_conversions_count
     * @return int
     */
    public function getNewConversionsCountAttribute(): int
    {
        $newSubscriptionsCountSql = <<<SQL
        select count(*) as subscriptions_count from (
            select c1.* from conversions c1
            left join conversions c2
            on c1.user_id = c2.user_id and c2.paid_at < c1.paid_at
            where c2.id is Null
            and c1.article_id = ?
        ) t
SQL;
        return DB::select($newSubscriptionsCountSql, [$this->id])[0]->subscriptions_count;
    }

    public function getHasImageVariantsAttribute(): bool
    {
        return count($this->variants_count['image']) > 1;
    }

    public function getHasTitleVariantsAttribute(): bool
    {
        return count($this->variants_count['title']) > 1;
    }

    /**
     * conversion_sources
     * @return Collection
     */
    public function getConversionSources(): Collection
    {
        return $this
            ->conversions()
            ->with('conversionSources')
            ->get()
            ->pluck('conversionSources')
            ->filter(function ($conversionSource) {
                return $conversionSource->isNotEmpty();
            })
            ->flatten();
    }

    // Mutators
    public function setPublishedAtAttribute($value)
    {
        if (!$value) {
            return;
        }
        $this->attributes['published_at'] = new Carbon($value);
    }

    // Scopes
    public function scopeMostReadByTimespent(Builder $query, Carbon $start, string $getBy, int $limit = null): Builder
    {
        $innerQuery = ArticleTimespent::where('time_from', '>=', $start)
            ->groupBy('article_id')
            ->select(['article_id', DB::raw("sum($getBy) as total_sum")])
            ->orderByDesc('total_sum');

        if ($limit) {
            $innerQuery->limit($limit);
        }

        return $query->joinSub($innerQuery, 't', function ($join) {
            $join->on('articles.id', '=', 't.article_id');
        })->orderByDesc('t.total_sum');
    }

    public function scopeMostReadByPageviews(Builder $query, Carbon $start, string $getBy, int $limit = null): Builder
    {
        $innerQuery = ArticlePageviews::where('time_from', '>=', $start)
            ->groupBy('article_id')
            ->select(['article_id', DB::raw("sum($getBy) as total_sum")])
            ->orderByDesc('total_sum');

        $query = $query->joinSub($innerQuery, 't', function ($join) {
                $join->on('articles.id', '=', 't.article_id');
        })
            ->orderByDesc('t.total_sum');

        if ($limit) {
            $query = $query->limit($limit);
        }

        return $query;
    }

    public function scopeMostReadByAveragePaymentAmount(Builder $query, Carbon $start, ?int $limit = null): Builder
    {
        $innerQuery = Conversion::where('paid_at', '>=', $start)
            ->groupBy('article_id')
            ->select(['article_id', DB::raw('avg(amount) as average')])
            ->orderByDesc('average');

        $query = $query->joinSub($innerQuery, 'c', function ($join) {
            $join->on('articles.id', '=', 'c.article_id');
        })->orderByDesc('c.average');

        if ($limit) {
            $query = $query->limit($limit);
        }

        return $query;
    }

    public function scopeMostReadByTotalPaymentAmount(Builder $query, Carbon $start, ?int $limit = null): Builder
    {
        $innerQuery = Conversion::where('paid_at', '>=', $start)
            ->groupBy('article_id')
            ->select(['article_id', DB::raw('sum(amount) as average')])
            ->orderByDesc('average');

        $query = $query->joinSub($innerQuery, 'c', function ($join) {
            $join->on('articles.id', '=', 'c.article_id');
        })->orderByDesc('c.average');

        if ($limit) {
            $query = $query->limit($limit);
        }

        return $query;
    }

    public function scopeIgnoreAuthorIds(Builder $query, array $authorIds): Builder
    {
        if ($authorIds) {
            $query->join('article_author', 'articles.id', '=', 'article_author.article_id')
                ->whereNotIn('article_author.author_id', $authorIds);
        }
        return $query;
    }

    public function scopeIgnoreContentTypes(Builder $query, array $contentTypes): Builder
    {
        if ($contentTypes) {
            $query->whereNotIn('content_type', $contentTypes);
        }
        return $query;
    }

    public function scopePublishedBetween(Builder $query, Carbon $from = null, Carbon $to = null): Builder
    {
        if ($from) {
            $query->where('published_at', '>=', $from);
        }
        if ($to) {
            $query->where('published_at', '<=', $to);
        }
        return $query;
    }
    
    public function scopeOfSelectedProperty(Builder $query): Builder
    {
        $tokenProvider = resolve(TokenProvider::class);
        $propertyUuid = $tokenProvider->getToken();
        if ($propertyUuid) {
            $query->where('articles.property_uuid', $propertyUuid);
        }
        return $query;
    }

    public static function computeConversionRate(
        $conversionsCount,
        $uniqueBrowsersCount,
        ConversionRateConfig $conversionRateConfig
    ): string {
        if ($uniqueBrowsersCount === 0) {
            return '0';
        }
        $multiplier = $conversionRateConfig->getMultiplier();
        $conversionRate = (float) ($conversionsCount / $uniqueBrowsersCount) * $multiplier;
        $conversionRate = number_format($conversionRate, $conversionRateConfig->getDecimalNumbers());

        if ($multiplier == 100) {
            return "$conversionRate %";
        }

        return $conversionRate;
    }

    // Resolvers

    /**
     * @deprecated Create your own instance of ConversionRateConfig.
     */
    protected function getConversionRateConfig()
    {
        $cacheDurationInSeconds = 60;
        $refreshAfter = $this->conversionRateConfigLastLoadTimestamp + $cacheDurationInSeconds;
        $needsRefresh = time() > $refreshAfter;

        if (!$this->conversionRateConfig || $needsRefresh) {
            $this->conversionRateConfig = ConversionRateConfig::build();
            $this->conversionRateConfigLastLoadTimestamp = time();
        }
        return $this->conversionRateConfig;
    }

    public function getJournal()
    {
        if (!$this->journal) {
            $this->journal = resolve(JournalContract::class);
        }
        return $this->journal;
    }

    public function getJournalHelpers()
    {
        if (!$this->journalHelpers) {
            $this->journalHelpers = new JournalHelpers($this->getJournal());
        }
        return $this->journalHelpers;
    }
}
