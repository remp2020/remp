<?php

namespace Remp\BeamModule\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Remp\BeamModule\Model\Article;

class ArticleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Article::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'external_id' => $this->faker->uuid,
            'title' => $this->faker->words(5, true),
            'url' => $this->faker->url,
            'content_type' => 'article',
            'image_url' => $this->faker->imageUrl(),
            'published_at' => $this->faker->dateTimeBetween('-30 days', 'now')->format(DATE_RFC3339),
            'pageviews_all' => $this->faker->numberBetween(0, 20000),
            'pageviews_signed_in' => $this->faker->numberBetween(0, 20000),
            'pageviews_subscribers' => $this->faker->numberBetween(0, 20000),
            'timespent_all' => $this->faker->numberBetween(0, 600000),
            'timespent_signed_in' => $this->faker->numberBetween(0, 600000),
            'timespent_subscribers' => $this->faker->numberBetween(0, 600000),
        ];
    }
}
