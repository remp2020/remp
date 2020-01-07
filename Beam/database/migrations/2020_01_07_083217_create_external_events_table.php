<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateExternalEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('external_events', function (Blueprint $table) {
            $table->increments('id');
            $table->string('type');
            $table->string('external_article_id')->nullable();
            $table->string('title');
            $table->text('detail')->nullable();
            $table->timestamp('happened_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('external_events');
    }
}
