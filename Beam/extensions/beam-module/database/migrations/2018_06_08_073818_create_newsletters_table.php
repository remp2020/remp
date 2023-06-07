<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNewslettersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('newsletters', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('mailer_generator_id')->unsigned();
            $table->string('segment');
            $table->string('mail_type_code');
            $table->string('criteria');
            $table->integer('articles_count')->unsigned();
            $table->text('recurrence_rule')->nullable();
            $table->integer('timespan')->unsigned();
            $table->string('state');
            $table->string('email_subject');
            $table->string('email_from');
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('newsletters');
    }
}
