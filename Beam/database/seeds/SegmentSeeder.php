<?php

use Illuminate\Database\Seeder;

class SegmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /** @var \App\Segment $segment */
        $segment = factory(\App\Segment::class)->create();

        /** @var \App\SegmentRule $rule */
        $rule = factory(\App\SegmentRule::class)->make();
        $segment->rules()->save($rule);
    }
}