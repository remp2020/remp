<?php

namespace App;

use App\Model\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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

    public function articles()
    {
        return $this->hasMany(Article::class);
    }
}
