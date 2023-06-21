<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Remp\BeamModule\Model\Account;
use Remp\BeamModule\Model\Property;

class PropertySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /** @var Account $account */
        $account = Account::factory()->create();

        /** @var Property $property */
        $property = Property::factory()->make();
        $account->properties()->save($property);
    }
}