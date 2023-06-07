<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRefererMediumLabelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('referer_medium_labels', function (Blueprint $table) {
            $table->increments('id');
            $table->string('referer_medium');
            $table->string('label');
            $table->timestamps();

            $table->unique(['referer_medium']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('referer_medium_labels');
    }
}
