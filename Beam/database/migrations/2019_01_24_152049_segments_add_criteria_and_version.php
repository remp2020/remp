<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SegmentsAddCriteriaAndVersion extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table("segments", function (Blueprint $table) {
            $table->integer('version')->nullable(true)->default(null);
            $table->json('criteria')->nullable(true)->comment('');
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
            $table->dropColumn('version');
            $table->dropColumn('criteria');
        });
    }
}
