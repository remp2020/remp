<?php

namespace Remp\BeamModule\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Remp\BeamModule\Database\Factories\SectionFactory;
use Remp\Journal\TokenProvider;

class Section extends BaseModel
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

    protected static function newFactory(): SectionFactory
    {
        return SectionFactory::new();
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class);
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
