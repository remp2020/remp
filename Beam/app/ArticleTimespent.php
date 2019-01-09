<?php

namespace App;

use App\Model\Aggregable;
use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ArticleTimespent extends Model implements Aggregable
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
        return ['sum', 'signed_in', 'subscribers'];
    }

    public function groupableFields(): array
    {
        return ['article_id'];
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
}
