<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SegmentRuleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \App\SegmentRule::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'event_category' => 'banner',
            'event_action' => 'show',
            'operator' => '<',
            'count' => $this->faker->numberBetween(1, 5),
            'timespan' => 1440 * $this->faker->numberBetween(1, 7),
            'fields' => [
                [
                    'key' => 'rtm_campaign',
                    'value' => null,
                ]
            ],
            'flags' => [],
        ];
    }
}
