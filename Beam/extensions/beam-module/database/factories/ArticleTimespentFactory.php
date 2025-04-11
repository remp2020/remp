<?php

namespace Remp\BeamModule\Database\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Remp\BeamModule\Model\ArticleTimespent;

class ArticleTimespentFactory extends Factory
{
    protected $model = ArticleTimespent::class;

    public function definition()
    {
        $sum = $this->faker->numberBetween(100, 400);
        $signedIn = $this->faker->numberBetween(1, 50);
        $subscribers = $sum - $signedIn;

        $timeTo = Carbon::instance($this->faker->dateTimeBetween('-30 days', 'now'));
        $timeFrom = (clone $timeTo)->subHour();

        return [
            'article_id' => function () {
                return \Remp\BeamModule\Model\Article::factory()->create()->id;
            },
            'time_from' => $timeFrom,
            'time_to' => $timeTo,
            'sum' => $sum,
            'signed_in' => $signedIn,
            'subscribers' => $subscribers
        ];
    }
}
