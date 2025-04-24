<?php
namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;
use Remp\CampaignModule\ShortMessageTemplate;

/** @extends Factory<ShortMessageTemplate> */
class ShortMessageTemplateFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Remp\CampaignModule\ShortMessageTemplate::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'text' => $this->faker->words(3, true),
            'color_scheme' => 'grey',
        ];
    }
}
