<?php

namespace Remp\BeamModule\Helpers;

use Illuminate\Support\Facades\Cache;

class Colors
{
    private const PREDEFINED_ORDER = ['internal', 'direct/IM', 'search', 'social', 'external', 'email'];

    private const CACHE_KEY_REFERER_MEDIUM_TAGS_ORDERING = 'referer_medium_tags_ordering';

    /**
     * Order referer medium tags by given rules
     * Rules applied in order:
     * 1. caching order (if enabled), all tags has to be present in the cached field otherwise not applied
     * 2. predefined order
     * 3. tag count order (descending)
     *
     * @param array $refererMediumTagCounts
     *
     * @return array list of ordered tags
     */
    public static function orderRefererMediumTags(array $refererMediumTagCounts): array
    {
        if (Cache::has(self::CACHE_KEY_REFERER_MEDIUM_TAGS_ORDERING)) {
            $cachedOrderedTags = Cache::get(self::CACHE_KEY_REFERER_MEDIUM_TAGS_ORDERING);
            // check if all tags are in the cached results
            $allTagsPresent = empty(array_diff(
                array_keys($refererMediumTagCounts),
                $cachedOrderedTags
            ));

            if ($allTagsPresent) {
                return array_values(array_intersect($cachedOrderedTags, array_keys($refererMediumTagCounts)));
            }
        }

        // First, take predefined order (flipped - keep tags as keys)
        $predefinedOrder = array_flip(array_intersect(self::PREDEFINED_ORDER, array_keys($refererMediumTagCounts)));

        // Second, take rest of the tags and sort them according to counts, descending
        $unorderedTags = array_diff_key($refererMediumTagCounts, $predefinedOrder);
        arsort($unorderedTags);

        $toReturn = array_merge(array_keys($predefinedOrder), array_keys($unorderedTags));
        Cache::forever(self::CACHE_KEY_REFERER_MEDIUM_TAGS_ORDERING, $toReturn);

        return $toReturn;
    }

    public static function assignColorsToGeneralTags(iterable $tags, $caching = true): array
    {
        $i = 0;
        $colors = [];
        foreach ($tags as $tag) {
            $colors[$tag] = self::generateColor('general_tag', $tag, $i++, true);
        }
        return $colors;
    }

    public static function assignColorsToVariantTags(iterable $tags, $caching = true): array
    {
        $predefined = [
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
            $colors[$tag] = $predefined[$tag] ?? self::generateColor('ab_test_variants', $tag, $i++, $caching);
        }
        return $colors;
    }

    public static function assignColorsToMediumRefers(iterable $mediumReferers, $caching = true): array
    {
        $predefined = [
            'internal' => '#E63952',
            'direct/IM' => '#00C7DF',
            'social' => '#FFC34A',
            'search' => '#DEDEDE',
            'external' => '#CDE092',
            'email' => '#3B40b6',
        ];

        $i = 0;
        $colors = [];
        foreach ($mediumReferers as $mediumReferer) {
            $colors[$mediumReferer] = $predefined[$mediumReferer] ?? self::generateColor('medium_referer', $mediumReferer, $i++, $caching);
        }
        return $colors;
    }

    private static function generateColor(string $colorType, string $tag, int $index, bool $caching): string
    {
        $cacheKey =  "COLOR::{$colorType}::$tag";

        if ($caching && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // predefined colors
        $colors = [
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

        if ($index < count($colors)) {
            $color = $colors[$index];
        } else {
            // random color
            $color = '#' . str_pad(dechex(random_int(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
        }

        if ($caching) {
            Cache::forever($cacheKey, $color);
        }

        return $color;
    }
}
