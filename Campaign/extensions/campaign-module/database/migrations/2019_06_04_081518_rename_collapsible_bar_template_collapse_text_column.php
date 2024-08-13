<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameCollapsibleBarTemplateCollapseTextColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('collapsible_bar_templates', function (Blueprint $table) {
            $table->renameColumn('collapse_text', 'header_text');
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
            $table->renameColumn('header_text', 'collapse_text');
        });
    }
}
