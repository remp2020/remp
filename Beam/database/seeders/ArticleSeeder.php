<?php

namespace Database\Seeders;

use Faker\Generator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Remp\BeamModule\Model\Article;
use Remp\BeamModule\Model\Author;
use Remp\BeamModule\Model\Conversion;
use Remp\BeamModule\Model\Property;
use Remp\BeamModule\Model\Section;
use Remp\BeamModule\Model\Tag;

class ArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(Generator $faker)
    {
        /** @var Property $property */
        $properties = Property::all();

        $sections = Section::factory()->count(3)->create();
        $tags = Tag::factory()->count(10)->create();

        /** @var Collection $articles */
        $articles = Article::factory()->count(50)->create([
            'property_uuid' => $properties->random()->uuid,
        ])->each(function (Article $article) use ($sections, $tags) {
            $article->sections()->save($sections[rand(0, count($sections)-1)]);
            $article->tags()->save($tags[rand(0, count($tags)-1)]);
        });

        $authors = Author::factory()->count(5)->create();
        $articles->each(function (Article $article) use ($authors) {
            $article->authors()->attach($authors->random());
        });

        $articles->each(function (Article $article) use ($faker) {
            $article->conversions()->saveMany(
                Conversion::factory()->count($faker->numberBetween(5,20))->make([
                    'article_id' => $article->id,
                ])
            );
        });
    }
}
