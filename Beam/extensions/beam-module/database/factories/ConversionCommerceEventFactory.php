<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

namespace Remp\BeamModule\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;
use Remp\BeamModule\Model\ConversionCommerceEvent;

class ConversionCommerceEventFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ConversionCommerceEvent::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $steps = ['checkout', 'payment', 'purchase', 'refund'];

        return [
            'time' => Carbon::now(),
            'step' => $steps[array_rand($steps)],
            'minutes_to_conversion' => $this->faker->numberBetween(1, 1000),
            'event_prior_conversion' => $this->faker->numberBetween(1, 10),
            'funnel_id' => $this->faker->numberBetween(1, 10),
            'amount' => $this->faker->numberBetween(5, 20),
            'currency' => $this->faker->randomElement(['EUR','USD']),
        ];
    }
}
