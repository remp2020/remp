<?php

use Remp\BeamModule\Model\Config\Config;
use Remp\BeamModule\Model\Config\ConfigCategory;
use Remp\BeamModule\Model\Config\ConfigNames;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateConfigCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('config_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code');
            $table->string('display_name');
            $table->timestamps();

            $table->unique('code');
        });

        Schema::table('configs', function (Blueprint $table) {
            $table->integer('config_category_id')
                ->unsigned()
                ->nullable()
                ->after('id');

            $table->foreign('config_category_id')->references('id')->on('config_categories');
        });

        // Create dashboard categories

        $dashboardCategory = ConfigCategory::firstOrCreate([
            'code' => ConfigCategory::CODE_DASHBOARD,
            'display_name' => 'Dashboard',
        ]);

        $dashboardConfigs = [
            ConfigNames::CONVERSION_RATE_MULTIPLIER,
            ConfigNames::CONVERSION_RATE_DECIMAL_NUMBERS,
            ConfigNames::CONVERSIONS_COUNT_THRESHOLD_LOW,
            ConfigNames::CONVERSIONS_COUNT_THRESHOLD_MEDIUM,
            ConfigNames::CONVERSIONS_COUNT_THRESHOLD_HIGH,
            ConfigNames::CONVERSION_RATE_THRESHOLD_LOW,
            ConfigNames::CONVERSION_RATE_THRESHOLD_MEDIUM,
            ConfigNames::CONVERSION_RATE_THRESHOLD_HIGH,
        ];

        Config::whereIn('name', $dashboardConfigs)
            ->update(['config_category_id' => $dashboardCategory->id]);

    }
}
