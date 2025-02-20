<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropPageviewDevicesAndReferers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table("session_devices", function (Blueprint $table) {
            $table->drop();
        });

        Schema::table("session_referers", function (Blueprint $table) {
            $table->drop();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('session_devices', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamp('time_from')->nullable();
            $table->timestamp('time_to')->nullable();
            $table->boolean('subscriber');
            $table->integer('count');
            $table->string('type')->nullable();
            $table->string('model')->nullable();
            $table->string('brand')->nullable();
            $table->string('os_name')->nullable();
            $table->string('os_version')->nullable();
            $table->string('client_type')->nullable();
            $table->string('client_name')->nullable();
            $table->string('client_version')->nullable();

            $table->index(['brand']);
            $table->index(['model']);
            $table->index(['type']);
            $table->index(['client_type']);
            $table->index(['client_name', 'client_version']);
            $table->index(['os_name', 'os_version']);

            $table->index(['time_from']);
            $table->index(['time_to']);
        });

        Schema::create('session_referers', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamp('time_from')->nullable();
            $table->timestamp('time_to')->nullable();
            $table->boolean('subscriber');
            $table->integer('count');
            $table->string('medium')->nullable();
            $table->string('source')->nullable();

            $table->index(['medium']);
            $table->index(['source']);
            $table->index(['subscriber']);

            $table->index(['time_from']);
            $table->index(['time_to']);
        });
    }
}
