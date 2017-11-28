<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlternativeBanner extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->integer('alt_banner_id')->unsigned()->nullable(true)->after('banner_id');
            $table->foreign('alt_banner_id')->references('id')->on('banners');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropForeign(['alt_banner_id']);
            $table->dropColumn("alt_banner_id");
        });
    }
}
