<?php

namespace App;

use App\Http\Requests\EntityRequest;
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

        return EntitySchema::createFromJsonSchema($this->attributes["schema"]);
    }

    /**
     * @param mixed $schema
     * @return string json schema
     */
    public function setSchemaAttribute($schema)
    {
        if (!(is_string($schema) && json_decode($schema) && json_last_error() === JSON_ERROR_NONE)) {
            if ($schema instanceof EntityRequest) {
                $schema = json_encode(EntitySchema::createFromRequest($schema));
            } elseif (is_array($schema)) {
                $schema = json_encode((new EntitySchema)->setParams($schema));
            }
        }

        return $this->attributes["schema"] = $schema;
    }
}
