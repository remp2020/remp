<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddManualEventsTrackingToBanners extends Migration
{
    public function up()
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->boolean('manual_events_tracking')->nullable()->default(false);
        });

        DB::statement('UPDATE banners SET manual_events_tracking = :value', [
            'value' => 0
        ]);

        Schema::table('banners', function (Blueprint $table) {
            $table->boolean('manual_events_tracking')->nullable(false)->default(false)->change();
        });
    }

    public function down()
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn('manual_events_tracking');
        });
    }
}
