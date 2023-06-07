<?php

use Remp\BeamModule\Model\SegmentGroup;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSegmentGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('segment_groups', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('code');
            $table->string('type');
            $table->integer('sorting');
            $table->timestamps();
        });

        $rempGroup = new SegmentGroup();
        $rempGroup->name = "REMP Segments";
        $rempGroup->code = SegmentGroup::CODE_REMP_SEGMENTS;
        $rempGroup->type = SegmentGroup::TYPE_RULE;
        $rempGroup->sorting = 100;
        $rempGroup->save();

        $authorsGroup = new SegmentGroup();
        $authorsGroup->name = "Author segments";
        $authorsGroup->code = SegmentGroup::CODE_AUTHORS_SEGMENTS;
        $authorsGroup->type = SegmentGroup::TYPE_EXPLICIT;
        $authorsGroup->sorting = 200;
        $authorsGroup->save();

        Schema::table('segments', function(Blueprint $table) use ($rempGroup) {
            $table->integer('segment_group_id')->default($rempGroup->id)->unsigned();
        });

        Schema::table('segments', function(Blueprint $table) {
            $table->integer('segment_group_id')->default(null)->unsigned()->change();
        });

        Schema::table('segments', function(Blueprint $table) {
            $table->foreign('segment_group_id')->references('id')->on('segment_groups');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('segments', function(Blueprint $table) {
            $table->dropForeign(['segment_group_id']);
            $table->dropColumn('segment_group_id');
        });
        Schema::dropIfExists('segment_groups');
    }
}
