<?php

use Illuminate\Database\Migrations\Migration;

class AlterConversionsRemoveOnUpdateFromPaidAtColumn extends Migration
{
    public function up()
    {
        // Due to the doctrine bug updating manually
        // https://github.com/laravel/framework/issues/16526
        $sql = 'ALTER TABLE `conversions` CHANGE `paid_at` `paid_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
        DB::update($sql);
        $sql = 'ALTER TABLE `conversions` ALTER COLUMN `paid_at` DROP DEFAULT';
        DB::update($sql);
    }

    public function down()
    {
        $sql = 'ALTER TABLE `conversions` CHANGE `paid_at` `paid_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
        DB::update($sql);
    }
}
