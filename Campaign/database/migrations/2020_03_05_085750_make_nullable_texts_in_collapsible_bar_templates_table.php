<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MakeNullableTextsInCollapsibleBarTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('collapsible_bar_templates', function (Blueprint $table) {
            $table->string('header_text')->nullable(true)->change();
            $table->string('main_text')->nullable(true)->change();
            $table->string('button_text')->nullable(true)->change();
            $table->string('collapse_text')->nullable(true)->change();
            $table->string('expand_text')->nullable(true)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('collapsible_bar_templates', function (Blueprint $table) {
            //
        });
    }
}
