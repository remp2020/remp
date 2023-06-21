<?php

use Remp\BeamModule\Model\Config\Config;
use Remp\BeamModule\Model\Config\ConfigNames;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('configs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->string('display_name');
            $table->text('value')->nullable();
            $table->text('description')->nullable();
            $table->string('type')->default('text');
            $table->integer('sorting')->default(10);
            $table->boolean('autoload')->default(true);
            $table->boolean('locked')->default(false);
            $table->timestamps();
        });

        $this->seedBeamDashboardConfigs();
    }


    /**
     * Required configs for Dashboard, values are empirically defined
     */
    private function seedBeamDashboardConfigs()
    {
        Config::firstOrCreate([
            'name' => ConfigNames::CONVERSIONS_COUNT_THRESHOLD_LOW,
            'display_name' => 'Conversions count threshold low',
            'type' => 'int',
            'value' => 3
        ]);
        Config::firstOrCreate([
            'name' => ConfigNames::CONVERSIONS_COUNT_THRESHOLD_MEDIUM,
            'display_name' => 'Conversions count threshold medium',
            'type' => 'int',
            'value' => 8
        ]);
        Config::firstOrCreate([
            'name' => ConfigNames::CONVERSIONS_COUNT_THRESHOLD_HIGH,
            'display_name' => 'Conversions count threshold high',
            'type' => 'int',
            'value' => 13
        ]);

        Config::firstOrCreate([
            'name' => ConfigNames::CONVERSION_RATE_THRESHOLD_LOW,
            'display_name' => 'Conversion rate threshold low',
            'type' => 'float',
            'value' => 3.0
        ]);
        Config::firstOrCreate([
            'name' => ConfigNames::CONVERSION_RATE_THRESHOLD_MEDIUM,
            'display_name' => 'Conversion rate threshold medium',
            'type' => 'float',
            'value' => 5.0
        ]);
        Config::firstOrCreate([
            'name' => ConfigNames::CONVERSION_RATE_THRESHOLD_HIGH,
            'display_name' => 'Conversion rate threshold high',
            'type' => 'float',
            'value' => 7.0
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('configs');
    }
}
