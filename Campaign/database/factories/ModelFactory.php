<?php

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

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Banner::class, function (Faker\Generator $faker) {
    return [
        'name' => 'DEMO banner',
        'uuid' => $faker->uuid,
        'transition' => $faker->randomElement(['fade', 'bounce', 'shake', 'none']),
        'target_url' => $faker->url,
        'text' => $faker->words(3, true),
        'dimensions' => $faker->randomElement(['medium_rectangle', 'landscape']),
        'text_align' => $faker->randomElement(['center', 'left', 'right']),
        'text_color' => $faker->hexColor,
        'font_size' => $faker->numberBetween(30, 50),
        'background_color' => $faker->hexColor,
        'position' => $faker->randomElement(['top_left', 'top_right', 'bottom_left', 'bottom_right']),
        'display_delay' => $faker->numberBetween(1000, 5000),
        'closeable' => $faker->boolean(),
        'created_at' => $faker->date(),
        'updated_at' => $faker->date(),
    ];
});

$factory->define(App\Campaign::class, function (Faker\Generator $faker) {
    return [
        'name' => 'DEMO campaign',
        'uuid' => $faker->uuid,
        'active' => true,
        'banner_id' => 1,
        'created_at' => $faker->date(),
        'updated_at' => $faker->date(),
    ];
});

$factory->define(App\CampaignSegment::class, function (Faker\Generator $faker) {
    return [
        'campaign_id' => 1,
        'code' => 'demo_segment',
        'provider' => 'remp_segment',
        'created_at' => $faker->date(),
        'updated_at' => $faker->date(),
    ];
});