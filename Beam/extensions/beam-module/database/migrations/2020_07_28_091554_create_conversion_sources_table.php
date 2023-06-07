<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateConversionSourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('conversions', function (Blueprint $table) {
            $table->addColumn('boolean', 'source_processed', ['default' => false, 'after' => 'events_aggregated']);
        });

        Schema::create('conversion_sources', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('conversion_id')->unsigned();
            $table->string('type');
            $table->string('referer_medium');
            $table->string('referer_source')->nullable();
            $table->string('referer_host_with_path')->nullable();
            $table->integer('article_id')->unsigned()->nullable();
            $table->timestamps();

            $table->foreign('conversion_id')->references('id')->on('conversions');
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
        Schema::table('conversions', function (Blueprint $table) {
            $table->dropColumn('source_processed');
        });
        Schema::dropIfExists('conversion_sources');
    }
}
