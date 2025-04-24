<?php
namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;
use Remp\CampaignModule\Campaign;

/** @extends Factory<Campaign> */
class CampaignFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Remp\CampaignModule\Campaign::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->words(2, true),
            'uuid' => $this->faker->uuid,
            'pageview_rules' => [],
            'devices' => [],
            'signed_in' => $this->faker->boolean(),
            'once_per_session' => $this->faker->boolean(),
            'url_filter' => 'everywhere',
            'source_filter' => 'everywhere',
        ];
    }
}
