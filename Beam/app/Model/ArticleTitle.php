<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ArticleTitle extends Model
{
    protected $fillable = [
        'variant',
        'title',
        'article_id',
    ];
}
