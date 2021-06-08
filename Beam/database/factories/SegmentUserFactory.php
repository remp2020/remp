<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SegmentUserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \App\SegmentUser::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => $this->faker->numberBetween(1, 1000000),
        ];
    }
}
