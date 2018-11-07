<?php

namespace App;

use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ArticlePageviews extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'article_id',
        'time_from',
        'time_to',
        'sum',
        'signed_in',
        'subscribers',
    ];

    protected $dates = [
        'time_from',
        'time_to',
    ];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public static function mostReadArticles(Carbon $start, string $getBy, $limit = null): Collection
    {
        $query = ArticlePageviews::where('time_from', '>=', $start)
            ->groupBy('article_id')
            ->select(['article_id', DB::raw("sum($getBy) as total_sum")])
            ->orderByDesc('total_sum');

        if ($limit) {
            $query->limit($limit);
        }

        return Article::joinSub($query, 'article_pageviews', function ($join) {
            $join->on('articles.id', '=', 'article_pageviews.article_id');
        })->get();
    }
}
