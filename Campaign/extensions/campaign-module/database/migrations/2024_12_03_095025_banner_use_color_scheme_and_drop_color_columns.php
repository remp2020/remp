<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BannerUseColorSchemeAndDropColorColumns extends Migration
{
    public function up()
    {
        // add color scheme column to all banner templates which support it
        Schema::table('bar_templates', function (Blueprint $table) {
            $table->string('color_scheme')->nullable()->after('button_text');
        });
        Schema::table('collapsible_bar_templates', function (Blueprint $table) {
            $table->string('color_scheme')->nullable()->after('initial_state');
        });
        Schema::table('medium_rectangle_templates', function (Blueprint $table) {
            $table->string('color_scheme')->nullable()->after('button_text');
        });
        Schema::table('newsletter_rectangle_templates', function (Blueprint $table) {
            $table->string('color_scheme')->nullable()->after('height');
        });
        Schema::table('overlay_rectangle_templates', function (Blueprint $table) {
            $table->string('color_scheme')->nullable()->after('image_link');
        });
        Schema::table('short_message_templates', function (Blueprint $table) {
            $table->string('color_scheme')->nullable()->after('text');
        });

        // set color schemes based on background color
        // (we used to derive the banner's color scheme by referencing the background color)
        $colorSchemes = config('banners.color_schemes');

        // added old background colors from before change (fix banner color contrasts: remp/remp#1379)
        $colorSchemes['blue']['oldBackgroundColor'] = '#00b7db';
        $colorSchemes['green']['oldBackgroundColor'] = '#009688';
        $colorSchemes['red']['oldBackgroundColor'] = '#e91e63';

        foreach ($colorSchemes as $code => $colorScheme) {
            $backgroundColors = array_filter([
                $colorScheme['backgroundColor'],
                $colorScheme['oldBackgroundColor'] ?? null,
            ]);

            DB::table('bar_templates')
                ->whereIn('background_color', $backgroundColors)
                ->update([
                    'color_scheme' => $code
                ]);
            DB::table('collapsible_bar_templates')
                ->whereIn('background_color', $backgroundColors)
                ->update([
                    'color_scheme' => $code
                ]);
            DB::table('medium_rectangle_templates')
                ->whereIn('background_color', $backgroundColors)
                ->update([
                    'color_scheme' => $code
                ]);
            DB::table('newsletter_rectangle_templates')
                ->whereIn('background_color', $backgroundColors)
                ->update([
                    'color_scheme' => $code
                ]);
            DB::table('overlay_rectangle_templates')
                ->whereIn('background_color', $backgroundColors)
                ->update([
                    'color_scheme' => $code
                ]);
            DB::table('short_message_templates')
                ->whereIn('background_color', $backgroundColors)
                ->update([
                    'color_scheme' => $code
                ]);
        }

        // drop old color columns and set `color_scheme` column to not nullable
        Schema::table('bar_templates', function (Blueprint $table) {
            $table->dropColumn('background_color');
            $table->dropColumn('text_color');
            $table->dropColumn('button_background_color');
            $table->dropColumn('button_text_color');

            $table->string('color_scheme')->nullable(false)->change();
        });

        Schema::table('collapsible_bar_templates', function (Blueprint $table) {
            $table->dropColumn('background_color');
            $table->dropColumn('text_color');
            $table->dropColumn('button_background_color');
            $table->dropColumn('button_text_color');

            $table->string('color_scheme')->nullable(false)->change();
        });

        Schema::table('medium_rectangle_templates', function (Blueprint $table) {
            $table->dropColumn('background_color');
            $table->dropColumn('text_color');
            $table->dropColumn('button_background_color');
            $table->dropColumn('button_text_color');

            $table->string('color_scheme')->nullable(false)->change();
        });

        Schema::table('newsletter_rectangle_templates', function (Blueprint $table) {
            $table->dropColumn('background_color');
            $table->dropColumn('text_color');
            $table->dropColumn('button_background_color');
            $table->dropColumn('button_text_color');

            $table->string('color_scheme')->nullable(false)->change();
        });

        Schema::table('overlay_rectangle_templates', function (Blueprint $table) {
            $table->dropColumn('background_color');
            $table->dropColumn('text_color');
            $table->dropColumn('button_background_color');
            $table->dropColumn('button_text_color');

            $table->string('color_scheme')->nullable(false)->change();
        });

        Schema::table('short_message_templates', function (Blueprint $table) {
            $table->dropColumn('background_color');
            $table->dropColumn('text_color');

            $table->string('color_scheme')->nullable(false)->change();
        });
    }

    public function down()
    {
        // create old color columns
        Schema::table('bar_templates', function (Blueprint $table) {
            $table->string('background_color')->nullable();
            $table->string('text_color')->nullable();
            $table->string('button_background_color')->nullable();
            $table->string('button_text_color')->nullable();
        });
        Schema::table('collapsible_bar_templates', function (Blueprint $table) {
            $table->string('background_color')->nullable();
            $table->string('text_color')->nullable();
            $table->string('button_background_color')->nullable();
            $table->string('button_text_color')->nullable();
        });
        Schema::table('medium_rectangle_templates', function (Blueprint $table) {
            $table->string('background_color')->nullable();
            $table->string('text_color')->nullable();
            $table->string('button_background_color')->nullable();
            $table->string('button_text_color')->nullable();
        });
        Schema::table('newsletter_rectangle_templates', function (Blueprint $table) {
            $table->string('background_color')->nullable();
            $table->string('text_color')->nullable();
            $table->string('button_background_color')->nullable();
            $table->string('button_text_color')->nullable();
        });
        Schema::table('overlay_rectangle_templates', function (Blueprint $table) {
            $table->string('background_color')->nullable();
            $table->string('text_color')->nullable();
            $table->string('button_background_color')->nullable();
            $table->string('button_text_color')->nullable();
        });
        Schema::table('short_message_templates', function (Blueprint $table) {
            $table->string('background_color')->nullable();
            $table->string('text_color')->nullable();
        });

        $colorSchemes = config('banners.color_schemes');
        foreach ($colorSchemes as $code => $colorScheme) {
            DB::table('bar_templates')
                ->where('color_scheme', $code)
                ->update([
                    'background_color' => $colorScheme['backgroundColor'],
                    'text_color' => $colorScheme['textColor'],
                    'button_background_color' => $colorScheme['buttonBackgroundColor'],
                    'button_text_color' => $colorScheme['buttonTextColor'],
                ]);

            DB::table('collapsible_bar_templates')
                ->where('color_scheme', $code)
                ->update([
                    'background_color' => $colorScheme['backgroundColor'],
                    'text_color' => $colorScheme['textColor'],
                    'button_background_color' => $colorScheme['buttonBackgroundColor'],
                    'button_text_color' => $colorScheme['buttonTextColor'],
                ]);

            DB::table('medium_rectangle_templates')
                ->where('color_scheme', $code)
                ->update([
                    'background_color' => $colorScheme['backgroundColor'],
                    'text_color' => $colorScheme['textColor'],
                    'button_background_color' => $colorScheme['buttonBackgroundColor'],
                    'button_text_color' => $colorScheme['buttonTextColor'],
                ]);

            DB::table('newsletter_rectangle_templates')
                ->where('color_scheme', $code)
                ->update([
                    'background_color' => $colorScheme['backgroundColor'],
                    'text_color' => $colorScheme['textColor'],
                    'button_background_color' => $colorScheme['buttonBackgroundColor'],
                    'button_text_color' => $colorScheme['buttonTextColor'],
                ]);

            DB::table('overlay_rectangle_templates')
                ->where('color_scheme', $code)
                ->update([
                    'background_color' => $colorScheme['backgroundColor'],
                    'text_color' => $colorScheme['textColor'],
                    'button_background_color' => $colorScheme['buttonBackgroundColor'],
                    'button_text_color' => $colorScheme['buttonTextColor'],
                ]);

            DB::table('short_message_templates')
                ->where('color_scheme', $code)
                ->update([
                    'background_color' => $colorScheme['backgroundColor'],
                    'text_color' => $colorScheme['textColor'],
                ]);
        }

        // drop `color_scheme` column
        Schema::table('bar_templates', function (Blueprint $table) {
            $table->dropColumn('color_scheme');

            $table->string('background_color')->nullable(false)->change();
            $table->string('text_color')->nullable(false)->change();
            $table->string('button_background_color')->nullable(false)->change();
            $table->string('button_text_color')->nullable(false)->change();
        });
        Schema::table('collapsible_bar_templates', function (Blueprint $table) {
            $table->dropColumn('color_scheme');

            $table->string('background_color')->nullable(false)->change();
            $table->string('text_color')->nullable(false)->change();
            $table->string('button_background_color')->nullable(false)->change();
            $table->string('button_text_color')->nullable(false)->change();
        });
        Schema::table('medium_rectangle_templates', function (Blueprint $table) {
            $table->dropColumn('color_scheme');

            $table->string('background_color')->nullable(false)->change();
            $table->string('text_color')->nullable(false)->change();
            $table->string('button_background_color')->nullable(false)->change();
            $table->string('button_text_color')->nullable(false)->change();
        });
        Schema::table('newsletter_rectangle_templates', function (Blueprint $table) {
            $table->dropColumn('color_scheme');

            $table->string('background_color')->nullable(false)->change();
            $table->string('text_color')->nullable(false)->change();
            $table->string('button_background_color')->nullable(false)->change();
            $table->string('button_text_color')->nullable(false)->change();
        });
        Schema::table('overlay_rectangle_templates', function (Blueprint $table) {
            $table->dropColumn('color_scheme');

            $table->string('background_color')->nullable(false)->change();
            $table->string('text_color')->nullable(false)->change();
            $table->string('button_background_color')->nullable(false)->change();
            $table->string('button_text_color')->nullable(false)->change();
        });
        Schema::table('short_message_templates', function (Blueprint $table) {
            $table->dropColumn('color_scheme');

            $table->string('background_color')->nullable(false)->change();
            $table->string('text_color')->nullable(false)->change();
        });

    }
}
