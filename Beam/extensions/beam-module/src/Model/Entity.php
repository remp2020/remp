<?php

namespace Remp\BeamModule\Model;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Entity extends BaseModel
{
    protected $table = 'entities';

    protected $fillable = [
        'name',
        'parent_id'
    ];

    public function params(): HasMany
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
