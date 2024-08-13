<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class VariantProportion extends Migration
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
                $table->integer('proportion')->nullable();
            });

            $rows = DB::select("SELECT * FROM campaign_banners WHERE control_group != 1");
            $counts = [];

            foreach ($rows as $row) {
                if (array_key_exists($row->campaign_id, $counts)) {
                    $counts[$row->campaign_id]++;
                } else {
                    $counts[$row->campaign_id] = 1;
                }
            }

            foreach ($counts as $campaignId => $count) {
                $proportion = intval(100 / $count);

                DB::statement("UPDATE campaign_banners SET proportion = {$proportion} WHERE campaign_id = {$campaignId} AND control_group != 1");
                DB::statement("UPDATE campaign_banners SET proportion = 0 WHERE campaign_id = {$campaignId} AND control_group = 1");
            }

            Schema::table('campaign_banners', function (Blueprint $table) {
                $table->integer('proportion')->nullable(false)->change();
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
            $table->dropColumn('proportion');
        });
    }
}
