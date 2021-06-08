<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PropertyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \App\Property::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => 'DEMO property',
            'uuid' => $this->faker->uuid,
            'account_id' => function () {
                return \App\Account::factory()->create()->id;
            },
        ];
    }
}
