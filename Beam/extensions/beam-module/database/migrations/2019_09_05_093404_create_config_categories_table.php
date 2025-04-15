<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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

        Artisan::call('db:seed', [
            '--class' => \Remp\BeamModule\Database\Seeders\ConfigSeeder::class,
            '--force' => true,
        ]);
    }
}
