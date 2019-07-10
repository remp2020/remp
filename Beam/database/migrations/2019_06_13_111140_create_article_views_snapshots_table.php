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
            $table->string('external_article_id');
            $table->string('derived_referer_medium')->nullable();
            $table->string('explicit_referer_medium')->nullable();
            $table->integer('count')->unsigned();
            $table->json('count_by_referer')->nullable();

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
