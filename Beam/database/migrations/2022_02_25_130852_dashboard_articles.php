<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DashboardArticles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dashboard_articles', function(Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('article_id')->unsigned();
            $table->integer('unique_browsers')->nullable();
            $table->timestamp('last_dashboard_time');
            $table->timestamps();

            $table->foreign('article_id')->references('id')->on('articles');
            $table->index('last_dashboard_time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('dashboard_articles');
    }
}
