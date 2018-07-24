<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateArticleAggregatedViewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('article_aggregated_views', function (Blueprint $table) {
            $table->integer('article_id')->unsigned();
            $table->string('user_id');
            $table->string('browser_id');
            $table->date('date');
            $table->integer('pageviews')->default(0);
            $table->integer('timespent')->default(0);

            $table->primary(['article_id', 'user_id', 'browser_id', 'date'], 'primary_index');
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
        Schema::dropIfExists('article_aggregated_views');
    }
}
