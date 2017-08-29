<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class BannerDisplayType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->string('display_type');
            $table->string('position')->nullable()->change();
            $table->integer('display_delay')->nullable()->change();
            $table->boolean('closeable')->nullable()->change();
            $table->string('target_selector')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->removeColumn('display_type');
            $table->string('position')->change();
            $table->integer('display_delay')->change();
            $table->boolean('closeable')->change();
        });
    }
}
