<?php

use Illuminate\Database\Migrations\Migration;

// This migration is fixing possible bug caused by MySQL's explicit_defaults_for_timestamp being disabled.
// Due to that we don't want to allow down migration so the bug would be introduced back.
//
// https://dev.mysql.com/doc/refman/5.6/en/server-system-variables.html#sysvar_explicit_defaults_for_timestamp
class RemovingPaidAtDefaults extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Doctrine doesn't work with timestamp defaults yet and throws an error. We need to use raw query.
        DB::statement("ALTER TABLE `conversions` ALTER COLUMN `paid_at` DROP DEFAULT");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // no action here
    }
}
