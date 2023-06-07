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

use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;
use Remp\BeamModule\Model\ArticleViewsSnapshot;

class ArticleViewsSnapshotFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ArticleViewsSnapshot::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
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
