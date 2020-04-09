<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;

class Author extends Model implements Searchable
{
    protected $fillable = [
        'name',
        'external_id',
    ];

    protected $hidden = [
        'id',
        'pivot',
    ];

    public function getSearchResult(): SearchResult
    {
        return new SearchResult($this, $this->name);
    }

    public function articles()
    {
        return $this->belongsToMany(Article::class);
    }

    public function latestPublishedArticle()
    {
        return $this->articles()->orderBy('published_at', 'DESC')->take(1);
    }

    public function conversions()
    {
        return $this->hasManyThrough(Conversion::class, ArticleAuthor::class, 'article_author.author_id', 'conversions.article_id', 'id', 'article_id');
    }
}
