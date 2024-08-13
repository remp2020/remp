<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class VariantControlGroup extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('campaign_banners', function (Blueprint $table) {
            $table->dropForeign(['banner_id']);
        });

        // DISABLING TRANSACTION in old migration
        // since it is already applied in production and it breaks php test
        // reason: https://github.com/laravel/framework/issues/35380
        //DB::transaction(function () {
            Schema::table('campaign_banners', function (Blueprint $table) {
                $table->integer('control_group')->nullable()->default(0);

                $table->integer('banner_id')->unsigned()->nullable()->change();
            });

            $campaigns = DB::select("SELECT campaign_id FROM campaign_banners GROUP BY campaign_id");

            foreach ($campaigns as $campaign) {
                $id = $campaign->campaign_id;

                DB::statement("INSERT INTO campaign_banners (`campaign_id`, `banner_id`, `variant`, `control_group`) VALUES({$id}, null, 'Control Group', 1)");
            }
        //});

        Schema::table('campaign_banners', function (Blueprint $table) {
            $table->foreign('banner_id')->references('id')->on('banners');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //DB::transaction(function () {
            DB::statement("DELETE FROM campaign_banners WHERE control_group = 1");

            Schema::table('campaign_banners', function (Blueprint $table) {
                $table->dropColumn('control_group');

                $table->integer('banner_id')->nullable(true);
            });

        //});
    }
}
