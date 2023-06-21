<?php

namespace Remp\BeamModule\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Remp\BeamModule\Model\SegmentUser;

class SegmentUserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SegmentUser::class;

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
