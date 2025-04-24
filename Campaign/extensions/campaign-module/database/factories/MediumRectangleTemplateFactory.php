<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Remp\CampaignModule\MediumRectangleTemplate;

/** @extends Factory<MediumRectangleTemplate> */
class MediumRectangleTemplateFactory extends Factory
{
    protected $model = MediumRectangleTemplate::class;

    public function definition()
    {
        return [
            'header_text' => $this->faker->words(1, true),
            'main_text' => $this->faker->words(3, true),
            'button_text' => $this->faker->words(1, true),
            'color_scheme' => 'grey',
        ];
    }
}
