<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCampaignBannerStatPurchasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaign_banner_stat_purchases', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('campaign_banner_stat_id')->unsigned();
            $table->decimal('sum',10,2);
            $table->string('currency');

            $table->foreign('campaign_banner_stat_id')->references('id')->on('campaign_banner_stats');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('campaign_banner_stat_sums');
    }
}
