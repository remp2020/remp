<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateArticleAggregatedViewsWithIdTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Recreating table, dumping old data
        Schema::dropIfExists('article_aggregated_views');
        
        Schema::create('article_aggregated_views', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('article_id')->unsigned();
            $table->string('user_id')->nullable();
            $table->string('browser_id')->nullable();
            $table->date('date');
            $table->integer('pageviews')->default(0);
            $table->integer('timespent')->default(0);
        
            $table->unique(['article_id', 'user_id', 'browser_id', 'date'], 'unique_index');
            $table->index('user_id');
            $table->index('browser_id');
            $table->index('date');
            $table->foreign('article_id', 'fk_article_id')->references('id')->on('articles');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
