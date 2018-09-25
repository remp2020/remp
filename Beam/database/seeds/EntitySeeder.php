<?php

use App\EntitySchema;
use Illuminate\Database\Seeder;
use App\Exceptions\EntitySchemaException;

class EntitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     * @throws EntitySchemaException
     */
    public function run()
    {
        $userEntity = new \App\Entity();

        $userEntity->name = "user";
        $userEntity->schema = new EntitySchema(json_encode([
            "type" => EntitySchema::JSON_SCHEMA_TYPE_OBJECT,
            "properties" => [
                "id" => [
                    "type" => EntitySchema::JSON_SCHEMA_TYPE_STRING
                ],
                "email" => [
                    "type" => EntitySchema::JSON_SCHEMA_TYPE_STRING
                ]
            ]
        ]));

        $userEntity->save();
    }
}
