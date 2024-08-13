<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CampaignBannersPivotTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaign_banners', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('campaign_id')->unsigned();
            $table->integer('banner_id')->unsigned();
            $table->string('variant');

            $table->foreign('banner_id')->references('id')->on('banners');
            $table->foreign('campaign_id')->references('id')->on('campaigns');
        });

        // migrate data to new structure
        foreach (DB::query()->from('campaigns')->get() as $campaign) {
            DB::table('campaign_banners')->insert([
                'campaign_id' => $campaign->id,
                'banner_id' => $campaign->banner_id,
                'variant' => 'A',
            ]);

            if ($campaign->alt_banner_id) {
                DB::table('campaign_banners')->insert([
                    'campaign_id' => $campaign->id,
                    'banner_id' => $campaign->alt_banner_id,
                    'variant' => 'B',
                ]);
            }
        }

        // refresh campaign cache
        foreach (\Remp\CampaignModule\Campaign::all() as $campaign) {
            $campaign->cache();
        }

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropForeign(['banner_id']);
            $table->dropForeign(['alt_banner_id']);

            $table->dropColumn(['banner_id', 'alt_banner_id']);
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
            $table->integer('banner_id')->unsigned()->nullable(true)->after('active');
            $table->integer('alt_banner_id')->unsigned()->nullable(true)->after('banner_id');

            $table->foreign('banner_id')->references('id')->on('banners');
            $table->foreign('alt_banner_id')->references('id')->on('banners');
        });

        // migrate data to old structure
        foreach (DB::query()->from('campaign_banners')->get() as $row) {
            $query = DB::table('campaigns')->where([
                'id' => $row->campaign_id,
            ]);

            if ($row->variant === 'A') {
                $query->update([
                    'banner_id' => $row->banner_id,
                ]);
            } elseif ($row->variant === 'B') {
                $query->update([
                    'alt_banner_id' => $row->banner_id,
                ]);
            }
        }

        // refresh campaign cache
        foreach (\Remp\CampaignModule\Campaign::all() as $campaign) {
            $campaign->cache();
        }

        Schema::table('campaigns', function (Blueprint $table) {
            $table->integer('banner_id')->unsigned()->nullable(false)->after('active')->change();
        });

        Schema::drop('campaign_banners');
    }
}
