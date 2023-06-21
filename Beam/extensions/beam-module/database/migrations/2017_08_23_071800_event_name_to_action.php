<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EventNameToAction extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('segment_rules', function (Blueprint $table) {
            $table->renameColumn('event_name', 'event_action');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('segment_rules', function (Blueprint $table) {
            $table->renameColumn('event_action', 'event_name');
        });
    }
}
