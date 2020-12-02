<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSectionsSegmentGroup extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $authorsGroup = new \App\SegmentGroup();
        $authorsGroup->name = "Section segments";
        $authorsGroup->code = \App\SegmentGroup::CODE_SECTIONS_SEGMENTS;
        $authorsGroup->type = \App\SegmentGroup::TYPE_EXPLICIT;
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
        \App\SegmentGroup::getByCode(\App\SegmentGroup::CODE_SECTIONS_SEGMENTS)->delete();
    }
}
