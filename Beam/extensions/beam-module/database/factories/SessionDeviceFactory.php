<?php

namespace Remp\BeamModule\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;
use Remp\BeamModule\Model\SessionDevice;

class SessionDeviceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SessionDevice::class;

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
            'type' => $this->faker->word,
            'model' => $this->faker->word,
            'brand' => $this->faker->word,
            'os_name' => $this->faker->word,
            'os_version' => $this->faker->numberBetween(1,10),
            'client_type' => $this->faker->word,
            'client_name' => $this->faker->word,
            'client_version' => $this->faker->numberBetween(1,10),
        ];
    }
}
