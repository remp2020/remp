<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Remp\CampaignModule\Banner;
use Remp\CampaignModule\HtmlTemplate;

/** @extends Factory<Banner> */
class BannerFactory extends Factory
{
    protected $model = Banner::class;

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
            'template' => Banner::TEMPLATE_HTML,
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Banner $banner) {
            // Every banner must have a template - create default if none exists
            if ($banner->template === Banner::TEMPLATE_HTML) {
                $existingTemplate = HtmlTemplate::where('banner_id', $banner->id)->first();
                if (!$existingTemplate) {
                    $template = new HtmlTemplate([
                        'text' => 'Default text',
                        'css' => '',
                        'dimensions' => 'medium',
                        'text_align' => 'left',
                        'text_color' => '#000000',
                        'font_size' => '14',
                        'background_color' => '#ffffff',
                    ]);
                    $template->banner_id = $banner->id;
                    $template->save();

                    // Clear relationship cache so fresh() will load the new template
                    $banner->unsetRelation('htmlTemplate');
                }
            }
        });
    }
}
