<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ArticlePageviewsArticleTimeFromIndex extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('article_pageviews', function (Blueprint $table) {
            $table->index(['article_id', 'time_from']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('index', function (Blueprint $table) {
            $table->dropIndex(['article_id', 'time_from']);
        });
    }
}
