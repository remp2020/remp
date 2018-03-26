<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PageviewDevicesAndReferers extends Migration
{
    public function up()
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
        });

        Schema::create('session_referers', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamp('time_from')->nullable();
            $table->timestamp('time_to')->nullable();
            $table->boolean('subscriber');
            $table->integer('count');
            $table->string('medium')->nullable();
            $table->string('source')->nullable();
        });
    }

    public function down()
    {
        Schema::drop('session_devices');
        Schema::drop('session_referers');
    }
}
