<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateConversionGeneralEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('conversion_general_events', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('conversion_id')->unsigned();

            $table->timestamp('time');
            $table->integer('minutes_to_conversion');
            $table->integer('event_prior_conversion')->unsigned();

            $table->string('action')->nullable();
            $table->string('category')->nullable();

            $table->string('utm_campaign')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_source')->nullable();

            $table->timestamps();

            $table->foreign('conversion_id')->references('id')->on('conversions');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('conversion_general_events');
    }
}
