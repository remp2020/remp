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
        Schema::rename('article_aggregated_views', 'article_aggregated_views_WITHOUT_ID');
        
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
            $table->foreign('article_id', 'fk_article_id')->references('id')->on('articles');
        });

        DB::statement('INSERT INTO article_aggregated_views (article_id, user_id, browser_id, date, pageviews, timespent)
SELECT article_id, if(user_id = "", null, user_id) as user_id, browser_id, date, pageviews, timespent
FROM article_aggregated_views_WITHOUT_ID');

        Schema::dropIfExists('article_aggregated_views_WITHOUT_ID');
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
