<?php

namespace Remp\BeamModule\Model;

use Remp\BeamModule\Database\Factories\TagCategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Remp\Journal\TokenProvider;

class TagCategory extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'external_id',
    ];

    protected $hidden = [
        'id',
        'pivot',
        'created_at',
        'updated_at'
    ];

    protected static function newFactory(): TagCategoryFactory
    {
        return TagCategoryFactory::new();
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function scopeOfSelectedProperty($query)
    {
        $tokenProvider = resolve(TokenProvider::class);
        $propertyUuid = $tokenProvider->getToken();
        if ($propertyUuid) {
            $query->whereHas('tags', function (Builder $tagsQuery) {
                $tagsQuery->ofSelectedProperty();
            });
        }
        return $query;
    }
}
