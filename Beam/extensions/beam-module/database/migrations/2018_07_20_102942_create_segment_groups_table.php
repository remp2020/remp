<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSegmentGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('segment_groups', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('code');
            $table->string('type');
            $table->integer('sorting');
            $table->timestamps();
        });

        Schema::table('segments', function(Blueprint $table) {
            $table->integer('segment_group_id')->unsigned();
        });

        Schema::table('segments', function(Blueprint $table) {
            $table->foreign('segment_group_id')->references('id')->on('segment_groups');
        });

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
        Schema::table('segments', function(Blueprint $table) {
            $table->dropForeign(['segment_group_id']);
            $table->dropColumn('segment_group_id');
        });
        Schema::dropIfExists('segment_groups');
    }
}
