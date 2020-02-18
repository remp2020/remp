<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Nicolaslopezj\Searchable\SearchableTrait;

class Author extends Model
{
    use SearchableTrait;

    protected $fillable = [
        'name',
    ];

    protected $hidden = [
        'id',
        'pivot',
    ];

    /**
     * Searchable rules.
     *
     * @var array
     */
    protected $searchable = [
        /**
         * Columns and their priority in search results.
         * Columns with higher values are more important.
         * Columns with equal values have equal importance.
         *
         * @var array
         */
        'columns' => [
            'authors.name' => 1,
        ]
    ];

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
