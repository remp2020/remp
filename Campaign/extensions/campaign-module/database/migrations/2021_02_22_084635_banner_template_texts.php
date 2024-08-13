<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class BannerTemplateTexts extends Migration
{
    public function up()
    {
        Schema::table('bar_templates', function(Blueprint $table) {
            $table->text('main_text')->change();
            $table->text('button_text')->change();
        });
        Schema::table('collapsible_bar_templates', function(Blueprint $table) {
            $table->text('header_text')->change();
            $table->text('main_text')->change();
            $table->text('button_text')->change();
        });
        Schema::table('medium_rectangle_templates', function(Blueprint $table) {
            $table->text('header_text')->change();
            $table->text('main_text')->change();
            $table->text('button_text')->change();
        });
        Schema::table('overlay_rectangle_templates', function(Blueprint $table) {
            $table->text('header_text')->change();
            $table->text('main_text')->change();
            $table->text('button_text')->change();
        });
        Schema::table('overlay_two_buttons_signature_templates', function(Blueprint $table) {
            $table->text('text_before')->change();
            $table->text('text_after')->change();
            $table->text('text_signature')->change();
        });
        Schema::table('short_message_templates', function(Blueprint $table) {
            $table->text('text')->change();
        });
    }

    public function down()
    {
        Schema::table('bar_templates', function(Blueprint $table) {
            $table->string('main_text')->change();
            $table->string('button_text')->change();
        });
        Schema::table('collapsible_bar_templates', function(Blueprint $table) {
            $table->string('header_text')->change();
            $table->string('main_text')->change();
            $table->string('button_text')->change();
        });
        Schema::table('medium_rectangle_templates', function(Blueprint $table) {
            $table->string('header_text')->change();
            $table->string('main_text')->change();
            $table->string('button_text')->change();
        });
        Schema::table('overlay_rectangle_templates', function(Blueprint $table) {
            $table->string('header_text')->change();
            $table->string('main_text')->change();
            $table->string('button_text')->change();
        });
        Schema::table('overlay_two_buttons_signature_templates', function(Blueprint $table) {
            $table->string('text_before')->change();
            $table->string('text_after')->change();
            $table->string('text_signature')->change();
        });
        Schema::table('short_message_templates', function(Blueprint $table) {
            $table->string('text')->change();
        });
    }
}
