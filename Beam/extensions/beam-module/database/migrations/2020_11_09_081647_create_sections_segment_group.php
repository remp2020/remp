<?php

use Illuminate\Database\Migrations\Migration;
use Remp\BeamModule\Model\SegmentGroup;

class CreateSectionsSegmentGroup extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $authorsGroup = new SegmentGroup();
        $authorsGroup->name = "Section segments";
        $authorsGroup->code = SegmentGroup::CODE_SECTIONS_SEGMENTS;
        $authorsGroup->type = SegmentGroup::TYPE_EXPLICIT;
        $authorsGroup->sorting = 200;
        $authorsGroup->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        SegmentGroup::getByCode(SegmentGroup::CODE_SECTIONS_SEGMENTS)->delete();
    }
}
