<?php

use Remp\BeamModule\Model\Config\ConfigCategory;
use Remp\BeamModule\Model\Config\ConfigNames;
use Remp\BeamModule\Model\Config\Config;

use Illuminate\Database\Migrations\Migration;

class SeedConfigValues3 extends Migration
{
    public function up()
    {
        $configCategory = ConfigCategory::where('code', ConfigCategory::CODE_DASHBOARD)->first();

        Config::firstOrCreate([
            'name' => ConfigNames::DASHBOARD_FRONTPAGE_REFERER,
            'display_name' => 'Dashboard front-page referer',
            'description' => 'For filtering traffic coming from a front page, please specify a referrer (e.g. https://dennikn.sk/, with trailing slash)',
            'type' => 'string',
            'value' => null, // by default, nothing specified
            'config_category_id' => $configCategory->id,
        ]);
    }
}
