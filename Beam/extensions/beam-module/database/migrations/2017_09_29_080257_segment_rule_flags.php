<?php

use Remp\BeamModule\Model\SegmentRule;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SegmentRuleFlags extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table("segment_rules", function (Blueprint $table) {
            $table->longText('flags')->comment("JSON encoded flags")->nullable();
        });
        DB::table("segment_rules")->update(['flags' => '[]']);
        Schema::table("segment_rules", function (Blueprint $table) {
            $table->longText('flags')->comment("JSON encoded flags")->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table("segment_rules", function (Blueprint $table) {
            $table->dropColumn('flags');
        });
    }
}
