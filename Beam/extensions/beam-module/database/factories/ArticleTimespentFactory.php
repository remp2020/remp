<?php

namespace Remp\BeamModule\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;
use Remp\BeamModule\Model\ArticleTimespent;

class ArticleTimespentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ArticleTimespent::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $sum = $this->faker->numberBetween(100, 400);
        $signedIn = $this->faker->numberBetween(1, 50);
        $subscribers = $sum - $signedIn;

        $timeTo = Carbon::instance($this->faker->dateTimeBetween('-30 days', 'now'));
        $timeFrom = (clone $timeTo)->subHour();

        return [
            'article_id' => function () {
                return \App\Article::factory()->create()->id;
            },
            'time_from' => $timeFrom,
            'time_to' => $timeTo,
            'sum' => $sum,
            'signed_in' => $signedIn,
            'subscribers' => $subscribers
        ];
    }
}
