<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class OverlayTwoButtonsSignature extends Migration
{
    public function up()
    {
        Schema::create('overlay_two_buttons_signature_templates', function (Blueprint $table) {
            $table->increments('id');
	        $table->integer('banner_id')->unsigned();
			$table->text('text_before')->nullable(true);
			$table->string('text_after')->nullable(true);
	        $table->string('text_btn_primary');
	        $table->string('text_btn_primary_minor')->nullable(true);
	        $table->string('text_btn_secondary')->nullable(true);
	        $table->string('text_btn_secondary_minor')->nullable(true);
			$table->string('target_url_secondary')->nullable(true);
			$table->string('signature_image_url')->nullable(true);
			$table->string('text_signature')->nullable(true);

	        $table->foreign('banner_id')->references('id')->on('banners');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('overlay_two_buttons_signature_templates');
    }
}
