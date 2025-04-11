<?php

namespace Remp\BeamModule\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Remp\BeamModule\Model\SegmentRule;

class SegmentRuleFactory extends Factory
{
    protected $model = SegmentRule::class;

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
