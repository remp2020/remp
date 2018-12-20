<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateConversionPageviewEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('conversion_pageview_events', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('conversion_id')->unsigned();

            $table->timestamp('time');
            $table->integer('minutes_to_conversion');
            $table->integer('event_prior_conversion')->unsigned();

            $table->integer('article_id')->unsigned();
            $table->boolean('locked')->nullable();
            $table->boolean('signed_in')->nullable();
            $table->integer('timespent')->unsigned()->nullable();

            $table->string('utm_campaign')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_source')->nullable();

            $table->timestamps();

            $table->foreign('conversion_id')->references('id')->on('conversions');
            $table->foreign('article_id')->references('id')->on('articles');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('conversion_pageview_events');
    }
}
