<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Remp\CampaignModule\ShortMessageTemplate;

/** @extends Factory<ShortMessageTemplate> */
class ShortMessageTemplateFactory extends Factory
{
    protected $model = ShortMessageTemplate::class;

    public function definition()
    {
        return [
            'text' => $this->faker->words(3, true),
            'color_scheme' => 'grey',
        ];
    }
}
