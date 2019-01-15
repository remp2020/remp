<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateConversionCommerceEventProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('conversion_commerce_event_products', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('conversion_commerce_event_id')->unsigned();
            $table->string('product_id');

            $table->foreign('conversion_commerce_event_id', 'event_foreign')
                ->references('id')->on('conversion_commerce_events')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('conversion_commerce_event_products');
    }
}
