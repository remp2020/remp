<?php

namespace Remp\BeamModule\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Remp\BeamModule\Model\Account;
use Remp\BeamModule\Model\Property;

class PropertyFactory extends Factory
{
    protected $model = Property::class;

    public function definition()
    {
        return [
            'name' => 'DEMO property',
            'uuid' => $this->faker->uuid,
            'account_id' => function () {
                return Account::factory()->create()->id;
            },
        ];
    }
}
