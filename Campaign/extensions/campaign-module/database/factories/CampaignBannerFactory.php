<?php
namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;
use Remp\CampaignModule\CampaignBanner;

/** @extends Factory<CampaignBanner> */
class CampaignBannerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Remp\CampaignModule\CampaignBanner::class;

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
