<?php

namespace App\Helpers;

class Colors
{
    public static function generalTagsToColors($tags): array
    {
        $colors = [
            '#E63952',
            '#00C7DF',
            '#FFC34A',
            '#DEDEDE',
            '#CDE092',
            '#3B40b6',
        ];

        $toReturn = [];

        for ($i = 0, $iMax = count($tags); $i < $iMax; $i++) {
            $toReturn[] = $colors[$i];
        }
        return $toReturn;
    }

    public static function refererMediumTagsToColors($tags): array
    {
        $tagColor = [
            'internal' => '#E63952',
            'direct' => '#00C7DF',
            'social' => '#FFC34A',
            'search' => '#DEDEDE',
            'external' => '#CDE092',
            'email' => '#3B40b6',
        ];

        // If an unexpected tag occurs, assign it one from additional colors
        $i = 0;
        $additionalColors = [
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

        $colors = [];

        foreach ($tags as $tag) {
            $colors[] = $tagColor[$tag] ?? $additionalColors[$i++];
        }
        return $colors;
    }
}
