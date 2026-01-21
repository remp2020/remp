<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('collections')
            ->where(function (Builder $query) {
                $query->whereNull('created_at')
                    ->orWhereNull('updated_at');
            })
            ->update([
                'created_at' => DB::raw('COALESCE(created_at, NOW())'),
                'updated_at' => DB::raw('COALESCE(updated_at, NOW())'),
            ]);
    }

    public function down(): void
    {
        DB::table('collections')
            ->update([
                'created_at' => null,
                'updated_at' => null,
            ]);
    }
};
