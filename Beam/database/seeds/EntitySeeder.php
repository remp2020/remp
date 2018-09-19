<?php

use Illuminate\Database\Seeder;

class EntitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $userEntity = new \App\Entity();

        $userEntity->name = "user";
        $userEntity->schema = json_encode([
            "properties" => [
                "id" => [
                    "type" => "String"
                ],
                "email" => [
                    "type" => "String",
                    "format" => "email"
                ]
            ],
            "required" => ["id"]
        ]);

        $userEntity->save();
    }
}
