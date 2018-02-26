<?php

if (! function_exists('blade_class')) {
    function blade_class(array $array)
    {
        $classes = [];
        foreach ($array as $key => $value) {
            if (is_int($key) and $value) {
                $classes[] = $value;
            }
            else if ($value) {
                $classes[] = $key;
            }
        }
        if ($classes) {
            return 'class="'.implode(' ', $classes).'"';
        }
        return '';
    }
}

if (! function_exists('routes_active')) {
    function route_active(array $routeNames, string $classes = '', string $activeClasses = '')
    {
        $currentRouteName = \Route::currentRouteName();

        $currentRouteSegmentsCount = count(explode(".", $currentRouteName));

        foreach ($routeNames as $routeName) {
            $passedRouteSegmentsCount = count(explode(".", $routeName));
            if (strpos($currentRouteName, $routeName) === 0 && abs($currentRouteSegmentsCount-$passedRouteSegmentsCount) <= 1) {
                return "class=\"{$classes} active {$activeClasses}\"";
            }
        }

        return "class=\"{$classes}\"";
    }
}
