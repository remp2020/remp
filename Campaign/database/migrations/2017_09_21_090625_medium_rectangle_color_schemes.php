<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MediumRectangleColorSchemes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('medium_rectangle_templates', function (Blueprint $table) {
            $table->string('text_color')->default('#ffffff');
            $table->string('button_background_color')->default('#000000');
            $table->string('button_text_color')->default('#ffffff');
        });
        Schema::table('medium_rectangle_templates', function (Blueprint $table) {
            $table->string('text_color')->default(null)->change();
            $table->string('button_background_color')->default(null)->change();
            $table->string('button_text_color')->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('medium_rectangle_templates', function (Blueprint $table) {
            $table->dropColumn(['text_color', 'button_background_color', 'button_text_color']);
        });
    }
}
