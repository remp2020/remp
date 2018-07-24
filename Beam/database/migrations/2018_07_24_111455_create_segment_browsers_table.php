<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSegmentBrowsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('segment_browsers', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('segment_id')->unsigned();
            $table->string('browser_id');
            $table->timestamps();

            $table->foreign('segment_id')->references('id')->on('segments');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('segment_browsers');
    }
}
