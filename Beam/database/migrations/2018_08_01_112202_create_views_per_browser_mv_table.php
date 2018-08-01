<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateViewsPerBrowserMvTable
 * Temporary migration, this table works as materialized view
 */
class CreateViewsPerBrowserMvTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('views_per_browser_mv', function (Blueprint $table) {
            $table->increments('id');
            $table->string('browser_id');
            $table->integer('total_views')->unsigned();
            $table->index('browser_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('views_per_browser_mv');
    }
}
