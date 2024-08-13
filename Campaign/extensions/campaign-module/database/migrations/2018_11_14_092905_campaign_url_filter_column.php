<?php

use Remp\CampaignModule\Campaign;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CampaignUrlFilterColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('url_filter');
            $table->json('url_patterns')->nullable();
        });

        DB::statement('UPDATE campaigns SET url_filter = :filter', [
            'filter' => Campaign::URL_FILTER_EVERYWHERE
        ]);

        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('url_filter')->nullable(false)->change();
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
            $table->dropColumn('url_filter');
            $table->dropColumn('url_patterns');
        });
    }
}
