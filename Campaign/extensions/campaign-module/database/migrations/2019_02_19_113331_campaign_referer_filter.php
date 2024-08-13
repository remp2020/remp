<?php

use Remp\CampaignModule\Campaign;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CampaignRefererFilter extends Migration
{
    public function up()
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('referer_filter');
            $table->json('referer_patterns')->nullable();
        });

        DB::statement('UPDATE campaigns SET referer_filter = :filter', [
            'filter' => Campaign::URL_FILTER_EVERYWHERE
        ]);

        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('referer_filter')->nullable(false)->change();
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
            $table->dropColumn('referer_filter');
            $table->dropColumn('referer_patterns');
        });
    }
}
