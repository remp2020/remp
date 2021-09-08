<?php

namespace App;

use App\Model\BaseModel;
use App\Model\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function scopeOfSelectedProperty($query)
    {
        return $query->whereHas('tags', function (Builder $tagsQuery) {
            $tagsQuery->ofSelectedProperty();
        });
    }
}
