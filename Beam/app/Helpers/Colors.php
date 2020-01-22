<?php

namespace App\Helpers;

class Colors
{
    // If an unexpected tag occurs, assign it one from additional colors
    private static $additionalColors = [
        '#3366cc',
        '#dc3912',
        '#ff9900',
        '#109618',
        '#990099',
        '#0099c6',
        '#dd4477',
        '#66aa00',
        '#b82e2e',
        '#316395',
        '#994499',
        '#22aa99',
        '#aaaa11',
        '#6633cc',
        '#e67300',
        '#8b0707',
        '#651067',
        '#329262',
        '#5574a6',
        '#3b3eac'
    ];

    public static function generalTagsToColors($tags, $withKeys = false): array
    {
        $i = 0;
        $colors = [];
        foreach ($tags as $tag) {
            $colors[$tag] = self::$additionalColors[$i++];
        }
        if ($withKeys) {
            return $colors;
        }

        return array_values($colors);
    }

    public static function abTestVariantTagsToColors($tags): array
    {
        $tagColor = [
            'A' => '#E63952',
            'B' => '#00C7DF',
            'C' => '#FFC34A',
            'D' => '#DEDEDE',
            'E' => '#CDE092',
            'F' => '#3B40b6',
        ];

        $i = 0;
        $colors = [];
        foreach ($tags as $tag) {
            $colors[] = $tagColor[$tag] ?? self::$additionalColors[$i++];
        }
        return $colors;
    }

    public static function refererMediumTagsToColors($tags, $withKeys = false): array
    {
        $tagColor = [
            'internal' => '#E63952',
            'direct/IM' => '#00C7DF',
            'social' => '#FFC34A',
            'search' => '#DEDEDE',
            'external' => '#CDE092',
            'email' => '#3B40b6',
        ];

        $i = 0;
        $colors = [];
        foreach ($tags as $tag) {
            $colors[$tag] = $tagColor[$tag] ?? self::$additionalColors[$i++];
        }
        if ($withKeys) {
            return $colors;
        }

        return array_values($colors);
    }
}
