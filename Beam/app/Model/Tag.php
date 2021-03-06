<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Article;
use App\TagCategory;

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
        return $this->hasMany(Article::class);
    }

    public function tagCategories()
    {
        return $this->belongsToMany(TagCategory::class);
    }
}
