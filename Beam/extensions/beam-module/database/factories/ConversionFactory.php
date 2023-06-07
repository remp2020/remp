<?php

namespace Remp\BeamModule\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Remp\BeamModule\Model\Conversion;

class ConversionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Conversion::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'amount' => $this->faker->numberBetween(5,50),
            'currency' => $this->faker->randomElement(['EUR','USD']),
            'paid_at' => $this->faker->dateTimeBetween('-30 days', 'now')->format(DATE_RFC3339),
            'transaction_id' => $this->faker->uuid,
        ];
    }
}
