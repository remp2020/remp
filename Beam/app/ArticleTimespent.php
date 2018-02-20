<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

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
}
