<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCampaignBannerPurchaseStatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaign_banner_purchase_stats', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('campaign_banner_id')->unsigned();
            $table->timestamp('time_from')->nullable();
            $table->timestamp('time_to')->nullable();

            $table->decimal('sum',10,2);
            $table->string('currency');

            $table->foreign('campaign_banner_id')->references('id')->on('campaign_banners');
            $table->index('time_from');
            $table->index('time_to');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('campaign_banner_purchase_stats');
    }
}
