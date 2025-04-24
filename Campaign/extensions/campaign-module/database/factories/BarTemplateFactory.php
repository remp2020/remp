<?php
namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;
use Remp\CampaignModule\BarTemplate;

/** @extends Factory<BarTemplate> */
class BarTemplateFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Remp\CampaignModule\BarTemplate::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'main_text' => $this->faker->words(3, true),
            'button_text' => $this->faker->words(1, true),
            'color_scheme' => 'grey',
        ];
    }
}
