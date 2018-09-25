<?php

namespace App;

use JsonSchema\Validator;
use JsonSchema\SchemaStorage;
use JsonSchema\Constraints\Factory;
use App\Http\Requests\EntityRequest;
use Illuminate\Database\Eloquent\Model;
use App\Exceptions\EntitySchemaException;

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
     * @throws EntitySchemaException
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

        $this->validateJsonSchema($schema);

        return $this->attributes["schema"] = $schema;
    }

    /**
     * Validate Json against JsonSchema draft.
     *
     * @param string $schema
     * @throws EntitySchemaException if generated schema is not valid
     */
    public function validateJsonSchema(string $schema)
    {
        $entitySchema = json_decode($schema);

        // load json schema draft-06
        $jsonSchemaDraftUrl = "http://json-schema.org/draft-06/schema#";
        $jsonSchemaDraft = json_decode(file_get_contents($jsonSchemaDraftUrl));

        // create schema storage and add draft for resolving references
        $schemaStorage = new SchemaStorage();
        $schemaStorage->addSchema($jsonSchemaDraftUrl, $jsonSchemaDraft);

        // create validator and validate generated schema against draft schema
        $jsonValidator = new Validator(new Factory($schemaStorage));
        $jsonValidator->validate($entitySchema, $jsonSchemaDraft);

        // throw exception with json encoded errors array if generated schema isnt valid
        if (!$jsonValidator->isValid()) {
            $errors = json_encode($jsonValidator->getErrors());
            throw new EntitySchemaException("not valid json schema: '{$errors}'");
        }
    }
}
