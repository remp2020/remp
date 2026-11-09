<?php

use App\Model\ExternalSegmentGroup;
use Illuminate\Database\Migrations\Migration;
use Remp\BeamModule\Model\Segment;
use Remp\BeamModule\Model\SegmentBrowser;
use Remp\BeamModule\Model\SegmentGroup;

class CreateSignedOutWinbackBrowsersSegment extends Migration
{
    private const SEGMENT_CODE = 'signed-out-winback-browsers';

    public function up()
    {
        $group = SegmentGroup::getByCode(ExternalSegmentGroup::CODE);
        if (!$group) {
            throw new RuntimeException("Segment group '" . ExternalSegmentGroup::CODE . "' not found.");
        }

        Segment::create([
            'code' => self::SEGMENT_CODE,
            'name' => 'Signed-out win-back browsers',
            'active' => true,
            'segment_group_id' => $group->id,
        ]);
    }

    public function down()
    {
        $segment = Segment::where('code', self::SEGMENT_CODE)->first();
        if (!$segment) {
            return;
        }

        SegmentBrowser::where('segment_id', $segment->id)->delete();
        $segment->delete();
    }
}
