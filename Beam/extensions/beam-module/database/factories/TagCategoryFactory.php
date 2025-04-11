<?php

namespace Remp\BeamModule\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Remp\BeamModule\Model\TagCategory;

class TagCategoryFactory extends Factory
{
    protected $model = TagCategory::class;

    public function definition()
    {
        return [
            'name' => $this->faker->domainWord,
            'external_id' => $this->faker->uuid,
        ];
    }
}
