<?php

namespace Remp\BeamModule\Model;

use Remp\BeamModule\Database\Factories\SectionFactory;
use Remp\BeamModule\Model\Article;
use Remp\BeamModule\Model\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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

    public function articles()
    {
        return $this->belongsToMany(Article::class);
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
