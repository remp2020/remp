<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ArticleTimespentSignedSubscribed extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('article_timespents', function(Blueprint $table) {
            $table->integer('signed_in')->default(0)->after('sum');
            $table->integer('subscribers')->default(0)->after('signed_in');
        });

        Schema::table('articles', function(Blueprint $table) {
            $table->renameColumn('timespent_sum', 'timespent_all');
        });

        Schema::table('articles', function(Blueprint $table) {
            $table->bigInteger('timespent_signed_in')->default(0)->after('timespent_all');
            $table->bigInteger('timespent_subscribers')->default(0)->after('timespent_signed_in');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('article_timespents', function(Blueprint $table) {
            $table->dropColumn(['signed_in', 'subscribers']);
        });

        Schema::table('articles', function(Blueprint $table) {
            $table->renameColumn('timespent_all', 'timespent_sum');
            $table->dropColumn(['timespent_signed_in', 'timespent_subscribers']);
        });
    }
}
