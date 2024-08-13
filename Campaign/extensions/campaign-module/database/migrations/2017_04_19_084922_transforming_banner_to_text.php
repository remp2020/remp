<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TransformingBannerToText extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->string('target_url');
            $table->dropColumn('storage_uri');
            $table->dropColumn('width');
            $table->dropColumn('height');
            $table->string('text');
            $table->string('dimensions');
            $table->string('text_align');
            $table->string('text_color');
            $table->string('font_size');
            $table->string('background_color');
            $table->string('position');
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
            $table->dropColumn(['target_url', 'text_color', 'background_color', 'position', 'text', 'dimensions', 'text_align', 'font_size']);
            $table->string('storage_uri');
            $table->integer('width');
            $table->integer('height');
        });
    }
}
