<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration `2021_02_22_084635_banner_template_texts` changed these columns from
 * `string` to `text` using `->change()` without restating `->nullable()`.
 *
 * Back then `->change()` was backed by Doctrine DBAL, which kept the attributes
 * that were not explicitly stated, so the columns remained nullable. Since
 * Laravel 11 `->change()` rebuilds the whole column definition and drops
 * everything that is not restated, so a database migrated from scratch (or
 * imported from the SQL schema dump) ends up with these columns as NOT NULL,
 * while long-running databases still have them nullable.
 *
 * This restores the originally intended nullability on both.
 */
return new class extends Migration
{
    private const array NULLABLE_COLUMNS = [
        'bar_templates' => ['main_text', 'button_text'],
        'collapsible_bar_templates' => ['header_text', 'main_text', 'button_text'],
        'medium_rectangle_templates' => ['header_text', 'main_text', 'button_text'],
        'overlay_rectangle_templates' => ['header_text', 'main_text', 'button_text'],
        'overlay_two_buttons_signature_templates' => ['text_before', 'text_after', 'text_signature'],
    ];

    public function up(): void
    {
        foreach (self::NULLABLE_COLUMNS as $tableName => $columns) {
            Schema::table($tableName, function (Blueprint $table) use ($columns) {
                foreach ($columns as $column) {
                    $table->text($column)->nullable(true)->change();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::NULLABLE_COLUMNS as $tableName => $columns) {
            // NOT NULL columns cannot hold NULLs, replace them with empty strings first
            foreach ($columns as $column) {
                DB::table($tableName)
                    ->whereNull($column)
                    ->update([$column => '']);
            }

            Schema::table($tableName, function (Blueprint $table) use ($columns) {
                foreach ($columns as $column) {
                    $table->text($column)->nullable(false)->change();
                }
            });
        }
    }
};
