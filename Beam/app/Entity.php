<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Entity extends Model
{
    protected $table = 'entities';

    protected $fillable = [
        'name',
        'parent_id'
    ];

    public function params()
    {
        return $this->hasMany(EntityParam::class)
                    ->withTrashed()
                    ->orderBy("id");
    }

    public function isRootEntity()
    {
        return is_null($this->parent_id) ? true : false;
    }
}
