<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ArticlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('external_id');
            $table->string('property_uuid');
            $table->string('title');
            $table->string('author')->nullable();
            $table->string('url');
            $table->string('section')->nullable();
            $table->string('image_url')->nullable();
            $table->timestamp('published_at');
            $table->timestamps();

            $table->foreign('property_uuid')->references('uuid')->on('properties');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('articles');
    }
}
