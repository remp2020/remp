<?php

namespace App;

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
    ];

    protected $dates = [
        'paid_at',
    ];

    public function article()
    {
        return $this->belongsTo(Article::class);
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

    public static function mostReadArticleIdsByAveragePayment(\Carbon\Carbon $start, $limit = null): Collection
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

    public static function mostReadArticleIdsByTotalPayment(\Carbon\Carbon $start, $limit = null): Collection
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
