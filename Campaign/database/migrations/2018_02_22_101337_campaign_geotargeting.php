<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CampaignGeotargeting extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // create countries table
        Schema::create('countries', function (Blueprint $table) {
            $table->string('iso_code', 5)->primary();
            $table->string('name');

        });

        // pivot table to connect countries to campaigns
        Schema::create('campaign_country', function (Blueprint $table) {
            $table->integer('campaign_id')->unsigned();
            $table->string('country_iso_code', 5);
            $table->boolean('blacklisted')->default(false);

            $table->unique(['campaign_id', 'country_iso_code'], 'campaign_country_unique');

            $table->foreign('campaign_id')->references('id')->on('campaigns');
            $table->foreign('country_iso_code')->references('iso_code')->on('countries');

        });

        Artisan::call('db:seed', array('--class' => CountrySeeder::class));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('campaign_country');
        Schema::drop('countries');
    }
}
