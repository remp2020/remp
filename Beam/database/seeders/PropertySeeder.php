<?php

namespace Database\Seeders;

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
        $account = \App\Account::factory()->create();

        /** @var \App\Property $property */
        $property = \App\Property::factory()->make();
        $account->properties()->save($property);
    }
}