<?php

namespace Remp\BeamModule\Model;

use Remp\BeamModule\Database\Factories\AuthorFactory;
use Remp\BeamModule\Model\Conversion;
use Remp\BeamModule\Model\Article;
use Remp\BeamModule\Model\ArticleAuthor;
use Remp\BeamModule\Model\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Remp\Journal\TokenProvider;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;

class Author extends BaseModel implements Searchable
{
    use HasFactory;

    protected $fillable = [
        'name',
        'external_id',
    ];

    protected $hidden = [
        'id',
        'pivot',
    ];

    protected static function newFactory(): AuthorFactory
    {
        return AuthorFactory::new();
    }

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
    
    public function scopeOfSelectedProperty($query)
    {
        $tokenProvider = resolve(TokenProvider::class);
        $propertyUuid = $tokenProvider->getToken();
        if ($propertyUuid) {
            $query->whereHas('articles', function (Builder $articlesQuery) {
                $articlesQuery->ofSelectedProperty();
            });
        }
        return $query;
    }
}
