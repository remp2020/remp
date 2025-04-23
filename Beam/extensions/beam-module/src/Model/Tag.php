<?php

namespace Remp\BeamModule\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Remp\BeamModule\Database\Factories\TagFactory;
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

    protected static function newFactory(): TagFactory
    {
        return TagFactory::new();
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class);
    }

    public function tagCategories(): BelongsToMany
    {
        return $this->belongsToMany(TagCategory::class);
    }

    // Scopes

    public function scopeOfSelectedProperty(Builder $query): Builder
    {
        $tokenProvider = resolve(TokenProvider::class);
        $propertyUuid = $tokenProvider->getToken();
        if ($propertyUuid) {
            $query->whereHas('articles', function (Builder $articlesQuery) {
                /** @var Builder|Article $articlesQuery */
                $articlesQuery->ofSelectedProperty();
            });
        }
        return $query;
    }
}
