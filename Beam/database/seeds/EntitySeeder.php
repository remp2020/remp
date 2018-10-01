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
        $userEntity = new App\Entity();

        $userEntity->name = "user";
        $userEntity->save();

        $userIdParam = new App\EntityParam();
        $userIdParam->name = "id";
        $userIdParam->type = App\EntityParam::TYPE_STRING;

        $userEmailParam = new App\EntityParam();
        $userEmailParam->name = "email";
        $userEmailParam->type = App\EntityParam::TYPE_STRING;


        $userEntity->params()->saveMany([
            $userIdParam,
            $userEmailParam,
        ]);
    }
}
