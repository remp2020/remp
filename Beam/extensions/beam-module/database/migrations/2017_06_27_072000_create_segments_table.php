<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSegmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('segments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('code');
            $table->boolean('active');
            $table->timestamps();
        });

        Schema::create('segment_rules', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('segment_id')->unsigned();
            $table->integer('parent_id')->unsigned()->nullable();
            $table->string('event_category');
            $table->string('event_name');
            $table->integer('timespan')->nullable();
            $table->integer('count');
            $table->longText('fields')->comment("JSON encoded fields");
            $table->timestamps();

            $table->foreign('segment_id')->references('id')->on('segments');
            $table->foreign('parent_id')->references('id')->on('segment_rules');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('segment_rules');
        Schema::dropIfExists('segments');
    }
}
