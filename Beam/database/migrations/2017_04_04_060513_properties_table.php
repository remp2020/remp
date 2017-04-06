<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PropertiesTable extends Migration
{
    public function up()
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uuid');
            $table->string('name');
            $table->integer('account_id')->unsigned();
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts');
        });
    }

    public function down()
    {
        Schema::drop('properties');
    }
}
