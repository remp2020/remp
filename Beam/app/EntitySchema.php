<?php

namespace App;

use App\Exceptions\EntitySchemaException;

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
    private $params = [];

    /**
     * @param string $json
     * @throws EntitySchemaException
     */
    public function __construct(string $json = null)
    {
        $schema = json_decode($json, true);

        if (!empty($json)) {
            $this->params = $this->parseJsonSchemaProperties($schema["properties"]);
        }
    }

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
    public function jsonSerialize()
    {
        $props = [];

        foreach ($this->params as $paramName => $param) {
            $prop = $param;

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

            $props[$paramName] = $prop;
        }

        return [
            "type" => self::JSON_SCHEMA_TYPE_OBJECT,
            "properties" => $props,
        ];
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return json_encode($this);
    }

    public function getParams()
    {
        return $this->params;
    }

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
}
