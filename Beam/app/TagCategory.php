<?php

namespace App;

use App\Model\Tag;
use Illuminate\Database\Eloquent\Model;

class TagCategory extends Model
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
