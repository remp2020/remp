<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ArticlePageviewsSignedInSubscriberColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('article_pageviews', function(Blueprint $table) {
            $table->integer('signed_in')->default(0)->after('sum');
            $table->integer('subscribers')->default(0)->after('signed_in');
        });

        Schema::table('articles', function(Blueprint $table) {
            $table->renameColumn('pageview_sum', 'pageviews_all');
        });

        Schema::table('articles', function(Blueprint $table) {
            $table->bigInteger('pageviews_signed_in')->default(0)->after('pageviews_all');
            $table->bigInteger('pageviews_subscribers')->default(0)->after('pageviews_signed_in');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('article_pageviews', function(Blueprint $table) {
            $table->dropColumn(['signed_in', 'subscribers']);
        });

        Schema::table('articles', function(Blueprint $table) {
            $table->renameColumn('pageviews_all', 'pageview_sum');
            $table->dropColumn(['pageviews_signed_in', 'pageviews_subscribers']);
        });
    }
}
