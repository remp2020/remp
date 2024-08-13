<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForceInitialToCollapsibleBar extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('collapsible_bar_templates', function (Blueprint $table) {
            $table->boolean('force_initial_state')->default(false);
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
            $table->dropColumn('force_initial_state');
        });
    }
}
