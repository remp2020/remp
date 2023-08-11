<?php

$definition = env('COMMANDS_MEMORY_LIMITS');
$limits = [];

if (!empty($definition)) {
    foreach (explode(',', $definition) as $commandLimit) {
        $config = explode('::', $commandLimit);
        if (count($config) !== 2 || empty($config[0]) || empty($config[1])) {
            throw new \Exception('invalid format of COMMANDS_MEMORY_LIMITS entry; expected "command::limit", got "' . $commandLimit . '"');
        }
        $limits[$config[0]] = $config[1];
    }
}

return [
    'commands_memory_limits' => $limits,
    'commands_overlapping_expires_at' => env('COMMANDS_OVERLAPPING_EXPIRES_AT', 15),
    'commands' => [
        'aggregate_article_views' => [
            'default_step' => env('AGGREGATE_ARTICLE_VIEWS_DEFAULT_STEP'),
        ],
    ],
];
