<?php

namespace App;

use App\Model\ArticleTitle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Remp\Journal\AggregateRequest;
use Remp\Journal\JournalContract;
use Yadakhov\InsertOnDuplicateKey;

class Article extends Model
{
    private $journal;

    private $cachedAttributes = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->journal = resolve(JournalContract::class);
    }

    use InsertOnDuplicateKey;

    protected $fillable = [
        'property_uuid',
        'external_id',
        'title',
        'author',
        'url',
        'section',
        'image_url',
        'published_at',
        'pageviews_all',
        'pageviews_signed_in',
        'pageviews_subscribers',
    ];

    protected $dates = [
        'published_at',
        'created_at',
        'updated_at',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class, 'property_uuid', 'uuid');
    }

    public function authors()
    {
        return $this->belongsToMany(Author::class);
    }

    public function sections()
    {
        return $this->belongsToMany(Section::class);
    }

    public function conversions()
    {
        return $this->hasMany(Conversion::class);
    }

    public function pageviews()
    {
        return $this->hasMany(ArticlePageviews::class);
    }

    public function timespent()
    {
        return $this->hasMany(ArticleTimespent::class);
    }

    public function articleTitles()
    {
        return $this->hasMany(ArticleTitle::class);
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
        $results = collect($this->journal->unique($r));
        $variants = [];

        foreach ($results as $result) {
            $titleVariant = $result->tags->title_variant;
            $imageVariant = $result->tags->image_variant;

            if (!array_key_exists($titleVariant, $variants)) {
                $variants[$titleVariant] = [];
            }
            $variants[$titleVariant][$imageVariant] = $result->count;
        }

        $this->cachedAttributes['variants_count'] = $variants;

        return $variants;
    }

    /**
     * unique_browsers_count
     * @return int
     */
    public function getUniqueBrowsersCountAttribute(): int
    {
        $total = 0;
        $variantsCount = $this->variants_count; // Retrieved from accessor
        foreach ($variantsCount as $title => $titleVariants) {
            foreach ($titleVariants as $image => $count) {
                $total += $count;
            }
        }
        return $total;
    }

    /**
     * conversion_rate
     * @return float
     */
    public function getConversionRateAttribute(): float
    {
        $uniqueBrowsersCount = $this->unique_browsers_count;
        if ($uniqueBrowsersCount === 0) {
            return 0.0;
        }

        return (float) ($this->conversions()->count() / $uniqueBrowsersCount) * 100;
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

    /**
     * has_image_variants
     * @return int
     */
    public function getHasImageVariantsAttribute(): bool
    {
        $imageNames = [];
        $variantsCount = $this->variants_count;
        foreach ($variantsCount as $titleName => $titleVariants) {
            foreach ($variantsCount as $imageName => $count) {
                if ($imageName !== '') {
                    $imageNames[$imageName] = true;
                }
            }
        }
        return count($imageNames) > 1;
    }

    /**
     * has_title_variants
     * @return bool
     */
    public function getHasTitleVariantsAttribute(): bool
    {
        $titleNames = [];
        $variantsCount = $this->variants_count;
        foreach ($variantsCount as $titleName => $titleVariants) {
            if ($titleName !== '') {
                $titleNames[$titleName] = true;
            }
        }
        return count($titleNames) > 1;
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
    public function scopeMostReadByTimespent(Builder $query, Carbon $start, string $getBy, int $limit = null)
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

    public function scopeMostReadByPageviews(Builder $query, Carbon $start, string $getBy, int $limit = null)
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

    public function scopeMostReadByAveragePaymentAmount(Builder $query, Carbon $start, ?int $limit = null)
    {
        $innerQuery = Conversion::where('paid_at', '>=', $start)
            ->groupBy('article_id')
            ->select(['article_id', DB::raw('avg(amount) as average')])
            ->orderByDesc('average');

        if ($limit) {
            $innerQuery->limit($limit);
        }

        return $query->joinSub($innerQuery, 'c', function ($join) {
            $join->on('articles.id', '=', 'c.article_id');
        })->orderByDesc('c.average');
    }

    public function scopeMostReadByTotalPaymentAmount(Builder $query, Carbon $start, ?int $limit = null)
    {
        $innerQuery = Conversion::where('paid_at', '>=', $start)
            ->groupBy('article_id')
            ->select(['article_id', DB::raw('sum(amount) as average')])
            ->orderByDesc('average');

        if ($limit) {
            $innerQuery->limit($limit);
        }

        return $query->joinSub($innerQuery, 'c', function ($join) {
            $join->on('articles.id', '=', 'c.article_id');
        })->orderByDesc('c.average');
    }

    public function scopeIgnoreAuthorIds(Builder $query, array $authorIds)
    {
        if ($authorIds) {
            $query->join('article_author', 'articles.id', '=', 'article_author.article_id')
                ->whereNotIn('article_author.author_id', $authorIds);
        }
        return $query;
    }

    public function scopePublishedBetween(Builder $query, Carbon $from = null, Carbon $to = null)
    {
        if ($from) {
            $query->where('published_at', '>=', $from);
        }
        if ($to) {
            $query->where('published_at', '<=', $to);
        }
        return $query;
    }

    /**
     * Update or create record in case it doesn't exist.
     */
    public static function upsert(array $values): Article
    {
        $attributes = (new static($values))->attributesToArray();
        $updateKeys = array_keys($attributes);
        $updateKeys[] = 'updated_at';
        // Timestamp values are not inserted automatically
        $attributes['updated_at'] = $attributes['created_at'] = Carbon::now()->toDateTimeString();
        static::insertOnDuplicateKey($attributes, $updateKeys);

        return static::where('external_id', $values['external_id'])->first();
    }
}
