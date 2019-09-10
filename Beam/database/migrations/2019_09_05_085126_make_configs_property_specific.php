<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MakeConfigsPropertySpecific extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('configs', function (Blueprint $table) {
            $table->integer('property_id')
                ->unsigned()
                ->nullable()
                ->after('display_name');

            $table->foreign('property_id')->references('id')->on('properties');
            $table->dropUnique(['name']);
            $table->unique(['name', 'property_id']);
        });
    }

    public function down()
    {
        Schema::table('configs', function (Blueprint $table) {
            $table->dropColumn('property_id');
        });
    }
}
