<?php

use Illuminate\Database\Seeder;

class PropertySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /** @var \App\Account $account */
        $account = factory(\App\Account::class)->create();

        /** @var \App\Property $property */
        $property = factory(\App\Property::class)->make();
        $account->properties()->save($property);
    }
}