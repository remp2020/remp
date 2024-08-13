<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CampaignSegments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaign_segments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer("campaign_id")->unsigned();
            $table->string("code");
            $table->string("provider");
            $table->timestamps();

            $table->foreign('campaign_id')->references('id')->on('campaigns');
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('segment_id');
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
            $table->string('segment_id');
        });
        Schema::drop('campaign_segments');
    }
}
