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
        'created_at' => $faker->date(),
        'updated_at' => $faker->date(),
    ];
});

$factory->define(\App\Account::class, function (Faker\Generator $faker) {
    return [
        'name' => 'DEMO account',
        'uuid' => $faker->uuid,
        'created_at' => $faker->date(),
        'updated_at' => $faker->date(),
    ];
});

$factory->define(\App\Segment::class, function (Faker\Generator $faker) {
    return [
        'name' => 'DEMO segment',
        'code' => 'demo-segment',
        'active' => true,
        'created_at' => $faker->date(),
        'updated_at' => $faker->date(),
    ];
});

$factory->define(\App\SegmentRule::class, function (Faker\Generator $faker) {
    return [
        'event_category' => 'demo',
        'event_action' => 'action',
        'timespan' => 1440 * $faker->numberBetween(1,7),
        'fields' => \Psy\Util\Json::encode([
            'myfield' => 'myvalue',
        ]),
        'created_at' => $faker->date(),
        'updated_at' => $faker->date(),
    ];
});