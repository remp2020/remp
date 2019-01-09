<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Yadakhov\InsertOnDuplicateKey;

class Article extends Model
{
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

    public function setPublishedAtAttribute($value)
    {
        if (!$value) {
            return;
        }
        $this->attributes['published_at'] = new Carbon($value);
    }

    // Scopes
    public function scopeMostReadByTimespent($query, Carbon $start, string $getBy, int $limit = null)
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

    public function scopeMostReadByPageviews($query, Carbon $start, string $getBy, int $limit = null)
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

    public function scopeMostReadByAveragePaymentAmount($query, Carbon $start, ?int $limit = null)
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

    public function scopeMostReadByTotalPaymentAmount($query, Carbon $start, ?int $limit = null)
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

    public function scopeIgnoreAuthorIds($query, array $authorIds)
    {
        if ($authorIds) {
            $query->join('article_author', 'articles.id', '=', 'article_author.article_id')
                ->whereNotIn('article_author.author_id', $authorIds);
        }
        return $query;
    }

    public function loadNewConversionsCount()
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

    public function loadRenewedConversionsCount()
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
