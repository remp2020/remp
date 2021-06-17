<?php

namespace App;

use App\Model\BaseModel;
use App\Model\Tag;

class TagCategory extends BaseModel
{
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
}
