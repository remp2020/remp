<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SegmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \App\Segment::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $segmentName = $this->faker->domainWord;
        return [
            'name' => $segmentName,
            'code' => "$segmentName-segment",
            'active' => true,
        ];
    }

    public function author()
    {
        return $this->state(['segment_group_id' => \App\SegmentGroup::getByCode(\App\SegmentGroup::CODE_AUTHORS_SEGMENTS)->id]);
    }
}
