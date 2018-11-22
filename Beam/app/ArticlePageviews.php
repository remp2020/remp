<?php

namespace App;

use App\Model\Aggregable;
use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ArticlePageviews extends Model implements Aggregable
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

    public function aggregatedFields(): array
    {
        return ['sum', 'signed_in', 'subscriber'];
    }

    protected $dates = [
        'time_from',
        'time_to',
    ];

    protected $casts = [
        'sum' => 'integer',
        'signed_in' => 'integer',
        'subscribers' => 'integer',
    ];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public static function mostReadArticles(Carbon $start, string $getBy, ?int $limit = null): Collection
    {
        $query = ArticlePageviews::where('time_from', '>=', $start)
            ->groupBy('article_id')
            ->select(['article_id', DB::raw("sum($getBy) as total_sum")])
            ->orderByDesc('total_sum');

        if ($limit) {
            $query->limit($limit);
        }

        return Article::joinSub($query, 't', function ($join) {
            $join->on('articles.id', '=', 't.article_id');
        })->orderByDesc('t.total_sum')->get();
    }
}
