<?php

use Remp\BeamModule\Model\Config\ConfigNames;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Remp\BeamModule\Model\Config\Config;

class RemoveLockedColumnInConfigs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('configs', function (Blueprint $table) {
            $table->dropColumn('locked');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('configs', function (Blueprint $table) {
            $table->boolean('locked')->default(false);
        });

        $lockedConfigs = [
            ConfigNames::AUTHOR_SEGMENTS_MIN_VIEWS,
            ConfigNames::AUTHOR_SEGMENTS_DAYS_IN_PAST
        ];

        Config::whereIn('name', $lockedConfigs)
            ->update(['locked' => true]);
    }
}
