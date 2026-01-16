<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('newsletter_rectangle_templates')
            ->whereNull('terms')
            ->orWhere('terms', '')
            ->update([
                'terms' => 'By clicking <em>Subscribe</em>, you agree with <a href="#">Terms & Conditions</a>'
            ]);

        Schema::table('newsletter_rectangle_templates', function (Blueprint $table) {
            $table->text('terms')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('newsletter_rectangle_templates', function (Blueprint $table) {
            $table->text('terms')->nullable(true)->change();
        });
    }
};
