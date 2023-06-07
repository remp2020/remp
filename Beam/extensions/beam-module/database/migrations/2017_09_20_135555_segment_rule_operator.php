<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SegmentRuleOperator extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('segment_rules', function (Blueprint $table) {
            $table->string('operator')->nullable()->default('<');
            $table->dropForeign('segment_rules_parent_id_foreign');
            $table->dropColumn('parent_id');
        });
        Schema::table('segment_rules', function (Blueprint $table) {
            $table->string('operator')->nullable(false)->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('segment_rules', function (Blueprint $table) {
            $table->dropColumn('operator');
            $table->integer('parent_id')->unsigned()->nullable();
            $table->foreign('parent_id')->references('id')->on('segment_rules');
        });
    }
}
