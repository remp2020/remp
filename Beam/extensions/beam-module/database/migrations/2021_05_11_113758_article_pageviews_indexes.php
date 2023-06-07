<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ArticlePageviewsIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // To speed up queries with filters (section, author)
        Schema::table('article_pageviews', function (Blueprint $table) {
            $table->index(['article_id', 'time_from', 'sum']);
        });

        // To speed up query for top articles without filters
        Schema::table('article_pageviews', function (Blueprint $table) {
            $table->index(['time_from', 'article_id', 'sum']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('article_pageviews', function (Blueprint $table) {
            $table->dropIndex(['article_id', 'time_from', 'sum']);
        });

        Schema::table('article_pageviews', function (Blueprint $table) {
            $table->dropIndex(['time_from', 'article_id', 'sum']);
        });
    }
}
