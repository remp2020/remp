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
$factory->define(\App\Property::class, function (Faker\Generator $faker) {
    return [
        'name' => 'DEMO property',
        'uuid' => $faker->uuid,
    ];
});

$factory->define(\App\Account::class, function (Faker\Generator $faker) {
    return [
        'name' => 'DEMO account',
        'uuid' => $faker->uuid,
    ];
});

$factory->define(\App\Segment::class, function (Faker\Generator $faker) {
    return [
        'name' => 'DEMO segment',
        'code' => 'demo-segment',
        'active' => true,
    ];
});

$factory->define(\App\SegmentRule::class, function (Faker\Generator $faker) {
    return [
        'event_category' => 'banner',
        'event_action' => 'show',
        'operator' => '<',
        'count' => $faker->numberBetween(1, 5),
        'timespan' => 1440 * $faker->numberBetween(1, 7),
        'fields' => [
            [
                'key' => 'utm_campaign',
                'value' => null,
            ]
        ],
        'flags' => [],
    ];
});

$factory->define(\App\Author::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->name(),
    ];
});

$factory->define(\App\Section::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->domainWord,
    ];
});

$factory->define(\App\Article::class, function (Faker\Generator $faker) {
    return [
        'external_id' => $faker->uuid,
        'title' => $faker->words(5, true),
        'url' => $faker->url,
        'image_url' => $faker->imageUrl(),
        'published_at' => $faker->dateTimeBetween('-30 days', 'now')->format(DATE_RFC3339),
    ];
});

$factory->define(\App\Conversion::class, function (Faker\Generator $faker) {
    return [
        'amount' => $faker->numberBetween(5,50),
        'currency' => $faker->randomElement(['EUR','USD']),
        'paid_at' => $faker->dateTimeBetween('-30 days', 'now')->format(DATE_RFC3339),
        'transaction_id' => $faker->uuid,
    ];
});
