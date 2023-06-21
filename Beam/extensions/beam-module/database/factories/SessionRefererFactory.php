<?php

namespace Remp\BeamModule\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;
use Remp\BeamModule\Model\SessionReferer;

class SessionRefererFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SessionReferer::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $timeTo = Carbon::instance($this->faker->dateTimeBetween('-30 days', 'now'));
        $timeFrom = (clone $timeTo)->subHour();

        return [
            'time_from' => $timeFrom,
            'time_to' => $timeTo,
            'subscriber' => $this->faker->boolean(50),
            'count' => $this->faker->numberBetween(1, 900),
            'medium' => $this->faker->word,
            'source' => $this->faker->word,
        ];
    }
}
