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

namespace Remp\BeamModule\Database\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Remp\BeamModule\Model\ArticleViewsSnapshot;

class ArticleViewsSnapshotFactory extends Factory
{
    protected $model = ArticleViewsSnapshot::class;

    public function definition()
    {
        $refererMediums = ['external', 'internal', 'direct', 'email', 'social'];

        return [
            'time' => Carbon::now(),
            'property_token' => $this->faker->uuid,
            'external_article_id' => $this->faker->numberBetween(9999, 10000000),
            'referer_medium' => $refererMediums[array_rand($refererMediums)],
            'count' => $this->faker->numberBetween(1, 1000)
        ];
    }
}
