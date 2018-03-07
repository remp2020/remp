<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ArticlePageviews extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('article_pageviews', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('article_id')->unsigned();
            $table->timestamp('time_from')->nullable();
            $table->timestamp('time_to')->nullable();
            $table->integer('sum');

            $table->foreign('article_id')->references('id')->on('articles');
            $table->index('time_from');
            $table->index('time_to');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('article_pageviews');
    }
}
