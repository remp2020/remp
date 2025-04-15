<?php

use Illuminate\Database\Migrations\Migration;

class SeedConfigValues3 extends Migration
{
    public function up()
    {
        Artisan::call('db:seed', [
            '--class' => \Remp\BeamModule\Database\Seeders\ConfigSeeder::class,
            '--force' => true,
        ]);
    }
}
