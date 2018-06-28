<?php

namespace App;

use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ArticleTimespent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'article_id',
        'time_from',
        'time_to',
        'sum',
    ];

    protected $dates = [
        'time_from',
        'time_to',
    ];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public static function getMostReadArticles(Carbon $start, string $getBy, int $limit): Collection
    {
        $articleIds = ArticleTimespent::where('time_from', '>=', $start)
            ->groupBy('article_id')
            ->select(['article_id', DB::raw("count($getBy) as total_sum")])
            ->orderByDesc('total_sum')
            ->limit($limit)
            ->get()->pluck('article_id');

        return Article::findMany($articleIds);
    }
}
