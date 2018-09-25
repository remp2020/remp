<?php

namespace App;

use App\Exceptions\EntitySchemaException;
use App\Http\Requests\EntityRequest;

class EntitySchema implements \JsonSerializable
{
    const TYPE_STRING = "string";
    const TYPE_STRING_ARRAY = "string_array";
    const TYPE_NUMBER = "number";
    const TYPE_NUMBER_ARRAY = "number_array";
    const TYPE_BOOLEAN = "boolean";
    const TYPE_DATETIME = "datetime";

    const JSON_SCHEMA_TYPE_OBJECT = "object";
    const JSON_SCHEMA_TYPE_ARRAY = "array";

    const JSON_SCHEMA_TYPE_NUMBER = "number";
    const JSON_SCHEMA_TYPE_STRING = "string";
    const JSON_SCHEMA_TYPE_BOOLEAN = "boolean";

    const JSON_SCHEMA_FORMAT_DATETIME = "date-time";

    const JSON_SCHEMA_ALLOWED_PARAM_TYPES = [
        self::JSON_SCHEMA_TYPE_ARRAY,
        self::JSON_SCHEMA_TYPE_STRING,
        self::JSON_SCHEMA_TYPE_NUMBER,
        self::JSON_SCHEMA_TYPE_BOOLEAN,
    ];

    const JSON_SCHEMA_ALLOWED_PARAM_TYPES_IN_ARRAY = [
        self::JSON_SCHEMA_TYPE_STRING,
        self::JSON_SCHEMA_TYPE_NUMBER
    ];

    // params (json schema properties) of the Entity
    protected $params = [];

    /**
     * @param $props
     * @return array
     * @throws EntitySchemaException
     */
    public function parseJsonSchemaProperties($props)
    {
        $params = [];

        foreach ($props as $propName => $prop) {
            $type = $prop["type"];

            $param = $prop;

            $param["name"] = $propName;

            // handle json schema date-time string format
            if ($type === self::JSON_SCHEMA_TYPE_STRING &&
                isset($prop["format"]) &&
                $prop["format"] === self::JSON_SCHEMA_FORMAT_DATETIME
            ) {
                $param["type"] = self::TYPE_DATETIME;
                unset($param["format"]);

            // handle json schema array
            } elseif ($type === self::JSON_SCHEMA_TYPE_ARRAY) {
                // check if array schema is valid
                if (!isset($prop["contains"]["type"]) ||
                    !is_string($prop["contains"]["type"]) ||
                    !in_array($prop["contains"]["type"], self::JSON_SCHEMA_ALLOWED_PARAM_TYPES_IN_ARRAY)
                ) {
                    throw new EntitySchemaException("not valid array param type for param: '{$propName}'");
                }

                $param["type"] = $prop["contains"]["type"] === self::JSON_SCHEMA_TYPE_STRING
                                                                ? self::TYPE_STRING_ARRAY
                                                                : self::TYPE_NUMBER_ARRAY;

                if (isset($prop["contains"]["enum"])) {
                    $param["enum"] = $prop["contains"]["enum"];
                }

                unset($param["contains"]);
            }

            $params[$propName] = $param;
        }

        return $params;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param array $params
     * @return self
     */
    public function setParams(array $params)
    {
        $this->params = $params;
        return $this;
    }

    /**
     * Return all available parameter types.
     *
     * @return array
     */
    public function getAllTypes()
    {
        return [
            self::TYPE_STRING => __("entities.types." . self::TYPE_STRING),
            self::TYPE_STRING_ARRAY => __("entities.types." . self::TYPE_STRING_ARRAY),
            self::TYPE_NUMBER => __("entities.types." . self::TYPE_NUMBER),
            self::TYPE_NUMBER_ARRAY => __("entities.types." . self::TYPE_NUMBER_ARRAY),
            self::TYPE_BOOLEAN => __("entities.types." . self::TYPE_BOOLEAN),
            self::TYPE_DATETIME => __("entities.types." . self::TYPE_DATETIME),
        ];
    }

    /**
     * Create and return EntitySchema parsed from JSON string.
     *
     * @param string $json
     * @return EntitySchema
     * @throws EntitySchemaException
     */
    public static function createFromJsonSchema(string $json)
    {
        $data = \GuzzleHttp\json_decode($json, true);
        $schema = new EntitySchema();
        $params = [];

        $props = $schema->parseJsonSchemaProperties($data["properties"]);

        foreach ($props as $propName => $propDesc) {
            $params[$propName] = array_merge(
                ["name" => $propName],
                $propDesc
            );
        }

        $schema->setParams($params);
        return $schema;
    }

    /**
     * Create and return EntitySchema filled with params from request.
     *
     * @param EntityRequest $request
     * @return EntitySchema
     */
    public static function createFromRequest(EntityRequest $request)
    {
        $params = $request->get("params");

        $schema = new EntitySchema();
        $schema->setParams($params);

        return $schema;
    }

    /**
     * Encoding to JSON.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        $props = [];

        foreach ($this->params as $paramName => $param) {
            $prop = $param;

            $propName = $prop["name"];
            unset($prop["name"]);

            if ($param["type"] === self::TYPE_DATETIME) {
                $prop["type"] = self::JSON_SCHEMA_TYPE_STRING;
                $prop["format"] = self::JSON_SCHEMA_FORMAT_DATETIME;
            } elseif (in_array($param["type"], [self::TYPE_STRING_ARRAY, self::TYPE_NUMBER_ARRAY])) {
                $prop["type"] = self::JSON_SCHEMA_TYPE_ARRAY;
                $prop["contains"] = [
                    "type" => $param["type"] === self::TYPE_STRING_ARRAY
                        ? self::JSON_SCHEMA_TYPE_STRING
                        : self::JSON_SCHEMA_TYPE_NUMBER,
                    "enum" => $prop["enum"] ?? null
                ];
                unset($prop["enum"]);
            }

            $props[$propName] = $prop;
        }

        return [
            "type" => self::JSON_SCHEMA_TYPE_OBJECT,
            "properties" => $props,
        ];
    }

    /**
     * Returns Json Schema representation.
     *
     * @return string
     */
    public function __toString()
    {
        return json_encode($this);
    }
}
