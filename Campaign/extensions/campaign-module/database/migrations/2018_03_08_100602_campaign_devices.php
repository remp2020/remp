<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CampaignDevices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->json("devices")->nullable(true);
        });

        DB::statement("UPDATE campaigns SET devices = '[\"desktop\", \"mobile\"]'");

        Schema::table('campaigns', function (Blueprint $table) {
            $table->json("devices")->nullable(false)->change();
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
            $table->dropColumn("devices");
        });
    }
}
