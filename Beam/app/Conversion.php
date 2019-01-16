<?php

namespace App;

use App\Model\ConversionCommerceEvent;
use App\Model\ConversionGeneralEvent;
use App\Model\ConversionPageviewEvent;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Class Conversion
 *
 * @property string articleExternalId
 *
 * @package App
 */
class Conversion extends Model
{
    protected $fillable = [
        'article_external_id',
        'transaction_id',
        'amount',
        'currency',
        'paid_at',
        'user_id',
        'events_aggregated',
    ];

    protected $dates = [
        'paid_at'
    ];

    protected $casts = [
        'events_aggregated' => 'boolean'
    ];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public function commerceEvents()
    {
        return $this->hasMany(ConversionCommerceEvent::class);
    }

    public function pageviewEvents()
    {
        return $this->hasMany(ConversionPageviewEvent::class);
    }

    public function generalEvents()
    {
        return $this->hasMany(ConversionGeneralEvent::class);
    }

    public function setArticleExternalIdAttribute($articleExternalId)
    {
        $article = Article::select()->where([
            'external_id' => $articleExternalId
        ])->first();
        if (!$article) {
            throw new ModelNotFoundException(sprintf('Unable to link conversion to article %s, no internal record found', $articleExternalId));
        }

        $this->article_id = $article->id;
    }

    public function setPaidAtAttribute($value)
    {
        if (!$value) {
            return;
        }
        $this->attributes['paid_at'] = new Carbon($value);
    }

    public static function mostReadArticlesByAveragePaymentAmount(\Carbon\Carbon $start, ?int $limit = null): Collection
    {
        $query = Conversion::where('paid_at', '>=', $start)
            ->groupBy('article_id')
            ->select(['article_id', DB::raw('avg(amount) as average')])
            ->orderByDesc('average');

        if ($limit) {
            $query->limit($limit);
        }

        return Article::joinSub($query, 'c', function ($join) {
            $join->on('articles.id', '=', 'c.article_id');
        })->orderByDesc('c.average')->get();
    }

    public static function mostReadArticlesByTotalPaymentAmount(\Carbon\Carbon $start, ?int $limit = null): Collection
    {
        $query = Conversion::where('paid_at', '>=', $start)
            ->groupBy('article_id')
            ->select(['article_id', DB::raw('sum(amount) as average')])
            ->orderByDesc('average');

        if ($limit) {
            $query->limit($limit);
        }

        return Article::joinSub($query, 'c', function ($join) {
            $join->on('articles.id', '=', 'c.article_id');
        })->orderByDesc('c.average')->get();
    }
}
