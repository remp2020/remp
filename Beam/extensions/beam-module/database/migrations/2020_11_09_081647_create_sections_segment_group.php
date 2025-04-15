<?php

use Illuminate\Database\Migrations\Migration;

class CreateSectionsSegmentGroup extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Artisan::call('db:seed', [
            '--class' => \Remp\BeamModule\Database\Seeders\SegmentGroupSeeder::class,
            '--force' => true,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
