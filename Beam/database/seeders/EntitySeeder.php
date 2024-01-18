<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Remp\BeamModule\Model\Entity;
use Remp\BeamModule\Model\EntityParam;

class EntitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (!Entity::where(['name' => 'user'])->exists()) {
            $userEntity = new Entity();

            $userEntity->name = "user";
            $userEntity->save();

            $userIdParam = new EntityParam();
            $userIdParam->name = "id";
            $userIdParam->type = EntityParam::TYPE_STRING;

            $userEmailParam = new EntityParam();
            $userEmailParam->name = "email";
            $userEmailParam->type = EntityParam::TYPE_STRING;


            $userEntity->params()->saveMany([
                $userIdParam,
                $userEmailParam,
            ]);
        }
    }
}
