<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('campaign_ip_ranges', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('campaign_id')->unsigned();
            $table->string('ip_from', 45);
            $table->string('ip_to', 45)->nullable();
            $table->boolean('blacklisted')->default(false);

            $table->foreign('campaign_id')->references('id')->on('campaigns');
        });
    }

    public function down()
    {
        Schema::drop('campaign_ip_ranges');
    }
};
