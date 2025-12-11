<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('banners')
            ->whereRaw("CAST(js_includes AS CHAR) = '[null]'")
            ->update(['js_includes' => '[]']);

        DB::table('banners')
            ->whereRaw("CAST(css_includes AS CHAR) = '[null]'")
            ->update(['css_includes' => '[]']);
    }
};
