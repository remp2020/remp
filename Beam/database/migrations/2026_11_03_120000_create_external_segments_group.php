<?php

use App\Model\ExternalSegmentGroup;
use Illuminate\Database\Migrations\Migration;
use Remp\BeamModule\Model\Segment;
use Remp\BeamModule\Model\SegmentBrowser;
use Remp\BeamModule\Model\SegmentGroup;
use Remp\BeamModule\Model\SegmentUser;

class CreateExternalSegmentsGroup extends Migration
{
    public function up()
    {
        SegmentGroup::firstOrCreate([
            'code' => ExternalSegmentGroup::CODE,
        ], [
            'name' => 'External segments',
            'type' => SegmentGroup::TYPE_EXPLICIT,
            'sorting' => 300,
        ]);
    }

    public function down()
    {
        $group = SegmentGroup::getByCode(ExternalSegmentGroup::CODE);
        if (!$group) {
            return;
        }

        $segmentIds = Segment::where('segment_group_id', $group->id)->pluck('id');
        SegmentBrowser::whereIn('segment_id', $segmentIds)->delete();
        SegmentUser::whereIn('segment_id', $segmentIds)->delete();
        Segment::whereIn('id', $segmentIds)->delete();
        $group->delete();
    }
}
