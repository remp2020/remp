<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexToConversionsPaidAtColumn extends Migration
{
    public function up()
    {
        Schema::table('conversions', function(Blueprint $table) {
            $table->index('paid_at');
        });
    }
}
