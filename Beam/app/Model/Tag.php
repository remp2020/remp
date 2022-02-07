<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Article;
use App\TagCategory;
use Remp\Journal\TokenProvider;

class Tag extends BaseModel
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

    public function articles()
    {
        return $this->belongsToMany(Article::class);
    }

    public function tagCategories()
    {
        return $this->belongsToMany(TagCategory::class);
    }

    // Scopes

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
