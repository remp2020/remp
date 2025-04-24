<?php

use Remp\CampaignModule\Banner;
use Remp\CampaignModule\HtmlTemplate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class BannerTemplates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('html_templates', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('banner_id')->unsigned();
            $table->string('text');
            $table->string('dimensions');
            $table->string('text_align');
            $table->string('text_color');
            $table->string('font_size');
            $table->string('background_color');

            $table->foreign('banner_id')->references('id')->on('banners');
            $table->timestamps();
        });

        Schema::create('medium_rectangle_templates', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('banner_id')->unsigned();
            $table->string('header_text');
            $table->string('main_text');
            $table->string('button_text');
            $table->string('background_color');

            $table->foreign('banner_id')->references('id')->on('banners');
            $table->timestamps();
        });

        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn('text');
            $table->dropColumn('dimensions');
            $table->dropColumn('text_align');
            $table->dropColumn('text_color');
            $table->dropColumn('font_size');
            $table->dropColumn('background_color');
            $table->string('template');
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
            $table->dropColumn('template');
            $table->string('text');
            $table->string('dimensions');
            $table->string('text_align');
            $table->string('text_color');
            $table->string('font_size');
            $table->string('background_color');
        });

        Schema::drop('medium_rectangle_templates');
        Schema::drop('html_templates');
    }
}
