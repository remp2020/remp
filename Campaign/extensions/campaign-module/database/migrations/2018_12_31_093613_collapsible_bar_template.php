<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CollapsibleBarTemplate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('collapsible_bar_templates', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('banner_id')->unsigned();
            $table->string('collapse_text');
            $table->string('main_text');
            $table->string('button_text');
            $table->string('background_color');
            $table->string('text_color');
            $table->string('button_background_color');
            $table->string('button_text_color');
            $table->string('initial_state');

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
        Schema::drop('collapsible_bar_templates');
    }
}
