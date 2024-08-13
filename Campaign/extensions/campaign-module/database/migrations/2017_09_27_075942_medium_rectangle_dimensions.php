<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MediumRectangleDimensions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('medium_rectangle_templates', function (Blueprint $table) {
            $table->string("width")->nullable();
            $table->string("height")->nullable();
            $table->string("header_text")->nullable()->change();
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
            $table->dropColumn(["width", "height"]);
            $table->string("header_text")->nullable(false)->change();
        });
    }
}
