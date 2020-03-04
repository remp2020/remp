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

use Carbon\Carbon;

$factory->define(\App\Property::class, function (Faker\Generator $faker) {
    return [
        'name' => 'DEMO property',
        'uuid' => $faker->uuid,
        'account_id' => function () {
            return factory(App\Account::class)->create()->id;
        },
    ];
});

$factory->define(\App\Account::class, function (Faker\Generator $faker) {
    return [
        'name' => 'DEMO account',
        'uuid' => $faker->uuid,
    ];
});

$factory->define(\App\Segment::class, function (Faker\Generator $faker) {
    $segmentName = $faker->domainWord;
    return [
        'name' => $segmentName,
        'code' => "$segmentName-segment",
        'active' => true,
    ];
});

$factory->state(\App\Segment::class, 'author', [
    'segment_group_id' => \App\SegmentGroup::getByCode(\App\SegmentGroup::CODE_AUTHORS_SEGMENTS)->id,
]);

$factory->define(\App\SegmentUser::class, function (Faker\Generator $faker) {
    return [
        'user_id' => $faker->numberBetween(1, 1000000),
    ];
});

$factory->define(\App\SegmentBrowser::class, function (Faker\Generator $faker) {
    return [
        'browser_id' => $faker->uuid,
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

$factory->define(\App\Model\Tag::class, function (Faker\Generator $faker) {
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
        'pageviews_all' => $faker->numberBetween(0, 20000),
        'pageviews_signed_in' => $faker->numberBetween(0, 20000),
        'pageviews_subscribers' => $faker->numberBetween(0, 20000),
        'timespent_all' => $faker->numberBetween(0, 600000),
        'timespent_signed_in' => $faker->numberBetween(0, 600000),
        'timespent_subscribers' => $faker->numberBetween(0, 600000),
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

$factory->define(\App\Conversion::class, function (Faker\Generator $faker) {
    return [
        'amount' => $faker->numberBetween(5,50),
        'currency' => $faker->randomElement(['EUR','USD']),
        'paid_at' => $faker->dateTimeBetween('-30 days', 'now')->format(DATE_RFC3339),
        'transaction_id' => $faker->uuid,
    ];
});

$factory->define(\App\ArticlePageviews::class, function (Faker\Generator $faker) {
    $sum = $faker->numberBetween(5, 10);
    $signedIn = $faker->numberBetween(1, 5);
    $subscribers = $sum - $signedIn;

    $timeTo = Carbon::instance($faker->dateTimeBetween('-30 days', 'now'));
    $timeFrom = (clone $timeTo)->subHour();

    return [
        'article_id' => function () {
            return factory(App\Article::class)->create()->id;
        },
        'time_from' => $timeFrom,
        'time_to' => $timeTo,
        'sum' => $sum,
        'signed_in' => $signedIn,
        'subscribers' => $subscribers
    ];
});

$factory->define(\App\ArticleTimespent::class, function (Faker\Generator $faker) {
    $sum = $faker->numberBetween(100, 400);
    $signedIn = $faker->numberBetween(1, 50);
    $subscribers = $sum - $signedIn;

    $timeTo = Carbon::instance($faker->dateTimeBetween('-30 days', 'now'));
    $timeFrom = (clone $timeTo)->subHour();

    return [
        'article_id' => function () {
            return factory(App\Article::class)->create()->id;
        },
        'time_from' => $timeFrom,
        'time_to' => $timeTo,
        'sum' => $sum,
        'signed_in' => $signedIn,
        'subscribers' => $subscribers
    ];
});

$factory->define(\App\SessionDevice::class, function (Faker\Generator $faker) {
    $timeTo = Carbon::instance($faker->dateTimeBetween('-30 days', 'now'));
    $timeFrom = (clone $timeTo)->subHour();

    return [
        'time_from' => $timeFrom,
        'time_to' => $timeTo,
        'subscriber' => $faker->boolean(50),
        'count' => $faker->numberBetween(1, 900),
        'type' => $faker->word,
        'model' => $faker->word,
        'brand' => $faker->word,
        'os_name' => $faker->word,
        'os_version' => $faker->numberBetween(1,10),
        'client_type' => $faker->word,
        'client_name' => $faker->word,
        'client_version' => $faker->numberBetween(1,10),
    ];
});

$factory->define(\App\SessionReferer::class, function (Faker\Generator $faker) {
    $timeTo = Carbon::instance($faker->dateTimeBetween('-30 days', 'now'));
    $timeFrom = (clone $timeTo)->subHour();

    return [
        'time_from' => $timeFrom,
        'time_to' => $timeTo,
        'subscriber' => $faker->boolean(50),
        'count' => $faker->numberBetween(1, 900),
        'medium' => $faker->word,
        'source' => $faker->word,
    ];
});

$factory->define(\App\Model\ArticleViewsSnapshot::class, function (Faker\Generator $faker) {
    $refererMediums = ['external', 'internal', 'direct', 'email', 'social'];

    return [
        'time' => Carbon::now(),
        'property_token' => $faker->uuid,
        'external_article_id' => $faker->numberBetween(9999, 10000000),
        'referer_medium' => $refererMediums[array_rand($refererMediums)],
        'count' => $faker->numberBetween(1, 1000)
    ];
});