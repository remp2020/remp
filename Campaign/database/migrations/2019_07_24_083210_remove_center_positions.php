<?php

use Illuminate\Database\Migrations\Migration;

class RemoveCenterPositions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sql = <<<SQL
UPDATE `banners` SET `position` = null WHERE `position` IN ('center', 'middle_left', 'middle_right');
SQL;

        \Illuminate\Support\Facades\DB::update($sql);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
