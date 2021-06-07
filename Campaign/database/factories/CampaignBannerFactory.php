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

class CampaignBannerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \App\CampaignBanner::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'control_group' => 0,
            'proportion' => 100,
            'weight' => 1,
            'uuid' => $this->faker->uuid,
        ];
    }
}
