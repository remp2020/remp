<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterNewslettersChangeTimespanType extends Migration
{
    public function up()
    {
        Schema::table('newsletters', function (Blueprint $table) {
            $table->string('timespan')->change();
        });
    }
}
