<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ArticlePageviews extends Model
{
    public $timestamps = false;

    protected $dates = [
        'time_from',
        'time_to',
    ];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}
