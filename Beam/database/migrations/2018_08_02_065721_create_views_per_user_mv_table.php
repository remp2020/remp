<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateViewsPerUserMvTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('views_per_user_mv', function (Blueprint $table) {
            $table->string('user_id');
            $table->integer('total_views_last_30_days')->unsigned()->default(0);
            $table->integer('total_views_last_60_days')->unsigned()->default(0);
            $table->integer('total_views_last_90_days')->unsigned()->default(0);
            $table->primary('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('views_per_user_mv');
    }
}
