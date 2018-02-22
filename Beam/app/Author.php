<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Author extends Model
{
    protected $fillable = [
        'name',
    ];

    protected $hidden = [
        'id',
        'pivot',
    ];

    public function articles()
    {
        return $this->belongsToMany(Article::class);
    }

    public function conversions()
    {
        return $this->hasManyThrough(Conversion::class, ArticleAuthor::class, 'article_author.author_id', 'conversions.article_id', 'id', 'article_id');
    }
}
