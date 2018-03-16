<?php

use App\Campaign;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CampaignMobileDesktopTargeting extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->json("platforms")->nullable(true);
        });

        foreach (Campaign::all() as $campaign) {
            $campaign->platforms = [Campaign::PLATFORM_DESKTOP, Campaign::PLATFORM_MOBILE];
            $campaign->save();
        }

        Schema::table('campaigns', function (Blueprint $table) {
            $table->json("platforms")->nullable(false)->change();
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
            $table->dropColumn("platforms");
        });
    }
}
