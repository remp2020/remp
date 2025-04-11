<?php

namespace Remp\BeamModule\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Remp\BeamModule\Model\SegmentUser;

class SegmentUserFactory extends Factory
{
    protected $model = SegmentUser::class;

    public function definition()
    {
        return [
            'user_id' => $this->faker->numberBetween(1, 1000000),
        ];
    }
}
