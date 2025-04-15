<?php

namespace Remp\BeamModule\Database\Seeders;

use Illuminate\Database\Seeder;
use Remp\BeamModule\Model\SegmentGroup;

class SegmentGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        SegmentGroup::firstOrCreate([
            'name' => 'REMP Segments',
            'code' => SegmentGroup::CODE_REMP_SEGMENTS,
            'type' => SegmentGroup::TYPE_RULE,
            'sorting' => 100,
        ]);

        SegmentGroup::firstOrCreate([
            'name' => 'Section segments',
            'code' => SegmentGroup::CODE_SECTIONS_SEGMENTS,
            'type' => SegmentGroup::TYPE_EXPLICIT,
            'sorting' => 200,
        ]);

        SegmentGroup::firstOrCreate([
            'name' => 'Author segments',
            'code' => SegmentGroup::CODE_AUTHORS_SEGMENTS,
            'type' => SegmentGroup::TYPE_EXPLICIT,
            'sorting' => 200,
        ]);
    }
}
