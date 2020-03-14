<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MakeTextsNullableInBanners extends Migration
{
    public function up()
    {
        Schema::table('collapsible_bar_templates', function (Blueprint $table) {
            $table->string('header_text')->nullable(true)->change();
            $table->string('main_text')->nullable(true)->change();
            $table->string('button_text')->nullable(true)->change();
            $table->string('collapse_text')->nullable(true)->change();
            $table->string('expand_text')->nullable(true)->change();
        });

        Schema::table('medium_rectangle_templates', function (Blueprint $table) {
            $table->string('header_text')->nullable(true)->change();
            $table->string('main_text')->nullable(true)->change();
            $table->string('button_text')->nullable(true)->change();
        });

        Schema::table('bar_templates', function (Blueprint $table) {
            $table->string('main_text')->nullable(true)->change();
            $table->string('button_text')->nullable(true)->change();
        });
    }

    public function down()
    {
    }
}
