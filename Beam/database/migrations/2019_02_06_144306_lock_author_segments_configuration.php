<?php

use App\Console\Commands\ComputeAuthorsSegments;
use App\Model\Config;
use Illuminate\Database\Migrations\Migration;

class LockAuthorSegmentsConfiguration extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Config::whereIn('name', ComputeAuthorsSegments::ALL_CONFIGS)->update([
            'locked' => true
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Config::whereIn('name', ComputeAuthorsSegments::ALL_CONFIGS)->update([
            'locked' => false
        ]);
    }
}
