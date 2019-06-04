<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddExpandAndCollapseTextToCollapsibleBarTemplate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('collapsible_bar_templates', function (Blueprint $table) {
            $table->string('collapse_text')->default('Collapse');
            $table->string('expand_text')->default('Expand');
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
            $table->dropColumn('collapse_text');
            $table->dropColumn('expand_text');
        });
    }
}
