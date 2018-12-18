<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class OverlayRectangleTemplates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('overlay_rectangle_templates', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('banner_id')->unsigned();
            $table->string('header_text')->nullable(true);
            $table->string('main_text')->nullable(true);
            $table->string('button_text')->nullable(true);
            $table->string('background_color')->nullable(true);
            $table->string('text_color')->nullable(true);
            $table->string('button_background_color');
            $table->string('button_text_color');
            $table->string('width')->nullable(true);
            $table->string('height')->nullable(true);
            $table->string('image_link')->nullable(true);

            $table->foreign('banner_id')->references('id')->on('banners');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('overlay_rectangle_templates');
    }
}
