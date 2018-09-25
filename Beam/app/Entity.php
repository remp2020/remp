<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Entity extends Model
{
    protected $fillable = [
        'name',
        'parent_id',
        'schema',
    ];

    /**
     * @return EntitySchema
     * @throws Exceptions\EntitySchemaException
     */
    public function getSchemaAttribute()
    {
        if (!isset($this->attributes["schema"])) {
            return new EntitySchema();
        }

        return new EntitySchema($this->attributes["schema"]);
    }
}
