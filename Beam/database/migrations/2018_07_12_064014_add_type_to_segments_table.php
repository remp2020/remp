<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTypeToSegmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Set each segment type to 'rule' by default
        Schema::table('segments', function (Blueprint $table) {
            $table->string('type')->default(\App\Segment::TYPE_RULE)->after('active');
        });

        // then remove default value
        Schema::table('segments', function (Blueprint $table) {
            $table->string('type')->default(null)->change();
        });

        Schema::table('segments', function (Blueprint $table) {
            $table->boolean('public')->default(true)->after('type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('segments', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
}
