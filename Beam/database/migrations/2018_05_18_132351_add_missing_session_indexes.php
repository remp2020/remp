<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMissingSessionIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('session_devices', function(Blueprint $table) {
            $table->index(['brand']);
            $table->index(['model']);
            $table->index(['type']);
            $table->index(['client_type']);
            $table->index(['client_name', 'client_version']);
            $table->index(['os_name', 'os_version']);

            $table->index(['time_from']);
            $table->index(['time_to']);
        });

        Schema::table('session_referers', function(Blueprint $table) {
            $table->index(['medium']);
            $table->index(['source']);
            $table->index(['subscriber']);

            $table->index(['time_from']);
            $table->index(['time_to']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('session_devices', function(Blueprint $table) {
            $table->dropIndex(['brand']);
            $table->dropIndex(['model']);
            $table->dropIndex(['type']);
            $table->dropIndex(['client_type']);
            $table->dropIndex(['client_name', 'client_version']);
            $table->dropIndex(['os_name', 'os_version']);

            $table->dropIndex(['time_from']);
            $table->dropIndex(['time_to']);
        });

        Schema::table('session_referers', function(Blueprint $table) {
            $table->dropIndex(['medium']);
            $table->dropIndex(['source']);
            $table->dropIndex(['subscriber']);

            $table->dropIndex(['time_from']);
            $table->dropIndex(['time_to']);
        });
    }
}
