<?php

namespace Remp\BeamModule\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Remp\BeamModule\Model\Segment;
use Remp\BeamModule\Model\SegmentGroup;

class SegmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Segment::class;

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
        return $this->state(['segment_group_id' => SegmentGroup::getByCode(SegmentGroup::CODE_AUTHORS_SEGMENTS)->id]);
    }
}
