<?php

use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(Faker\Generator $faker)
    {
        /** @var \App\Property $property */
        $properties = \App\Property::all();

        $sections = factory(\App\Section::class, 3)->create();
        $tags = factory(\App\Model\Tag::class, 10)->create();

        /** @var \Illuminate\Database\Eloquent\Collection $articles */
        $articles = factory(\App\Article::class, 50)->create([
            'property_uuid' => $properties->random()->uuid,
        ])->each(function (\App\Article $article) use ($sections, $tags) {
            $article->sections()->save($sections[rand(0, count($sections)-1)]);
            $article->tags()->save($tags[rand(0, count($tags)-1)]);
        });

        $authors = factory(\App\Author::class, 5)->create();
        $articles->each(function (\App\Article $article) use ($authors) {
            $article->authors()->attach($authors->random());
        });

        $articles->each(function (\App\Article $article) use ($faker) {
            $article->conversions()->saveMany(
                factory(\App\Conversion::class, $faker->numberBetween(5,20))->make([
                    'article_id' => $article->id,
                ])
            );
        });
    }
}
