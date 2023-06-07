<?php

use Illuminate\Database\Migrations\Migration;

class PurgeInvalidAggregatedData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // We've identified timezone-related issue which caused this data to be off. This data is not meant to be
        // persistent anyway, so we can get rid of them and try to aggregate them again correctly.

        // Get rid of any flawed data.
        DB::statement("SET foreign_key_checks = 0");
        DB::statement("TRUNCATE TABLE conversion_commerce_event_products");
        DB::statement("TRUNCATE TABLE conversion_commerce_events");
        DB::statement("TRUNCATE TABLE conversion_general_events");
        DB::statement("TRUNCATE TABLE conversion_pageview_events");
        DB::statement("SET foreign_key_checks = 1");

        // Make sure all conversions will get aggregated and processed again.
        DB::statement("UPDATE conversions SET events_aggregated = 1, source_processed = 1 WHERE paid_at < NOW() - INTERVAL 90 DAY");
        DB::statement("UPDATE conversions SET events_aggregated = 0, source_processed = 0 WHERE paid_at >= NOW() - INTERVAL 90 DAY");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // down migration not available
    }
}
