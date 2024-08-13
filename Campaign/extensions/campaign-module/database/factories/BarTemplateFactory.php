<?php
namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;


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
            'text_color' => '#000000',
            'background_color' => '#f7bc1e',
            'button_text_color' => '#ffffff',
            'button_background_color' => '#000000',
        ];
    }
}
