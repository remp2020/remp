<?php

namespace Remp\BeamModule\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Remp\BeamModule\Model\SegmentBrowser;

class SegmentBrowserFactory extends Factory
{
    protected $model = SegmentBrowser::class;

    public function definition()
    {
        return [
            'browser_id' => $this->faker->uuid,
        ];
    }
}
