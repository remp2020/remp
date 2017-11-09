<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class BannerCloseableRequired extends Migration
{
    public function up()
    {
        DB::getPdo()->exec('UPDATE banners SET closeable = false WHERE closeable IS NULL');
        Schema::table('banners', function (Blueprint $table) {
            $table->boolean("closeable")->nullable(false)->change();
        });
    }

    public function down()
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->boolean("closeable")->nullable(true)->change();
        });
    }
}
