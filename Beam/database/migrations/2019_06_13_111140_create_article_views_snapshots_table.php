<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateArticleViewsSnapshotsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('article_views_snapshots', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamp('time');
            $table->string('property_token');
            $table->integer('article_id')->unsigned();
            $table->string('derived_referer_medium')->nullable();
            $table->string('explicit_referer_medium')->nullable();
            $table->integer('count')->unsigned();

            $table->foreign('article_id')->references('id')->on('articles');
            $table->index('time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('article_views_snapshots');
    }
}
