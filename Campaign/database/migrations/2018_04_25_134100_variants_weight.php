<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class VariantsWeight extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // DISABLING TRANSACTION in old migration
        // since it is already applied in production and it breaks php test
        // reason: https://github.com/laravel/framework/issues/35380
        //DB::transaction(function () {
            Schema::table('campaign_banners', function (Blueprint $table) {
                $table->integer('weight')->nullable();
            });

            $variants = DB::select("SELECT * FROM campaign_banners WHERE control_group = 0 ORDER BY variant");
            $weight = 1;

            foreach($variants as $variant) {
                $campaignId = $variant->campaign_id;
                $bannerId = $variant->banner_id;

                DB::statement("UPDATE campaign_banners SET `weight` = {$weight} WHERE campaign_id = {$campaignId} AND banner_id = {$bannerId}");

                $weight++;
            }

            DB::statement("UPDATE campaign_banners SET `weight` = {$weight} WHERE control_group = 1");

            Schema::table('campaign_banners', function (Blueprint $table) {
                $table->integer('weight')->nullable(false)->change();
            });
        //});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('campaign_banners', function (Blueprint $table) {
            $table->dropColumn('weight');
        });
    }
}
