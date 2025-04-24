<?php
namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;
use Remp\CampaignModule\Banner;

/** @extends Factory<Banner> */
class BannerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Remp\CampaignModule\Banner::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'uuid' => $this->faker->uuid,
            'name' => $this->faker->word,
            'transition' => $this->faker->randomElement(['fade', 'bounce', 'shake', 'none']),
            'target_url' => $this->faker->url,
            'position' => $this->faker->randomElement(['top_left', 'top_right', 'bottom_left', 'bottom_right']),
            'display_delay' => $this->faker->numberBetween(1000, 5000),
            'display_type' => 'overlay',
            'offset_horizontal' => 0,
            'offset_vertical' => 0,
            'closeable' => $this->faker->boolean,
            'target_selector' => '#test',
            'manual_events_tracking' => 0,
        ];
    }
}
