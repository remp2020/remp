<?php

$config = [
    'dimensions' => [
        'landscape' => [
            'name' => 'Landscape (728x90)',
            'width' => '728px',
            'height' => '90px',
        ],
        'medium_rectangle' => [
            'name' => 'Medium rectangle (300x250)',
            'width' => '300px',
            'height' => '250px',
        ],
        'bar' => [
            'name' => 'Bar (728x50)',
            'width' => '728px',
            'height' => '50px',
        ],
        'notification' => [
            'name' => 'Notification (300x50)',
            'width' => '300px',
            'height' => '50px',
        ],
        'square' => [
            'name' => 'Square (300x300)',
            'width' => '300px',
            'height' => '300px',
        ],
        'full_width' => [
            'name' => 'Full width',
            'width' => '100%',
            'height' => 'auto',
        ],
        'hidden' => [
            'name' => 'Hidden (tracking) / JS-based',
            'width' => '0px',
            'height' => '0px',
        ]
    ],
    'positions' => [
        'top_left' => [
            'name' => 'Top-Left',
            'style' => [
                'top' => '0px',
                'left' => '0px',
            ],
        ],
        'top_right' => [
            'name' => 'Top-Right',
            'style' => [
                'top' => '0px',
                'right' => '0px',
            ],
        ],
        'bottom_left' => [
            'name' => 'Bottom-Left',
            'style' => [
                'bottom' => '0px',
                'left' => '0px',
            ],
        ],
        'bottom_right' => [
            'name' => 'Bottom-Right',
            'style' => [
                'bottom' => '0px',
                'right' => '0px',
            ],
        ],
    ],
    'alignments' => [
        'left' => [
            'name' => 'Left',
            'style' => [
                'text-align' => 'left',
                'justify-content' => 'flex-start',
            ],
        ],
        'center' => [
            'name' => 'Center',
            'style' => [
                'text-align' => 'center',
                'justify-content' => 'center',
            ],
        ],
        'right' => [
            'name' => 'Right',
            'style' => [
                'text-align' => 'right',
                'justify-content' => 'flex-end',
            ],
        ],
    ],
    'color_schemes' => [
        'grey' => [
            'label' => 'Grey',
            'textColor' => '#000000',
            'backgroundColor' => '#ededed',
            'buttonTextColor' => '#ffffff',
            'buttonBackgroundColor' => '#000000',
            'closeTextColor' => '#000000'
        ],
        'yellow' => [
            'label' => 'Yellow',
            'textColor' => '#000000',
            'backgroundColor' => '#f7bc1e',
            'buttonTextColor' => '#ffffff',
            'buttonBackgroundColor' => '#000000',
            'closeTextColor' => '#000000'
        ],
        'blue' => [
            'label' => 'Blue',
            'textColor' => '#ffffff',
            'backgroundColor' => '#008099',
            'buttonTextColor' => '#ffffff',
            'buttonBackgroundColor' => '#000000',
            'closeTextColor' => '#000000'
        ],
        'dark_blue' => [
            'label' => 'Dark Blue',
            'textColor' => '#ffffff',
            'backgroundColor' => '#1f3f82',
            'buttonTextColor' => '#000000',
            'buttonBackgroundColor' => '#ffffff',
            'closeTextColor' => '#000000'
        ],
        'green' => [
            'label' => 'Green',
            'textColor' => '#ffffff',
            'backgroundColor' => '#008577',
            'buttonTextColor' => '#ffffff',
            'buttonBackgroundColor' => '#000000',
            'closeTextColor' => '#000000'
        ],
        'violet' => [
            'label' => 'Violet',
            'textColor' => '#ffffff',
            'backgroundColor' => '#9c27b0',
            'buttonTextColor' => '#ffffff',
            'buttonBackgroundColor' => '#000000',
            'closeTextColor' => '#000000'
        ],
        'red' => [
            'label' => 'Red',
            'textColor' => '#ffffff',
            'backgroundColor' => '#E3165B',
            'buttonTextColor' => '#ffffff',
            'buttonBackgroundColor' => '#000000',
            'closeTextColor' => '#000000'
        ],
        'dark_red' => [
            'label' => 'Dark red',
            'textColor' => '#ffffff',
            'backgroundColor' => '#b00c28',
            'buttonTextColor' => '#ffffff',
            'buttonBackgroundColor' => '#000000',
            'closeTextColor' => '#000000'
        ],
        'black' => [
            'label' => 'Black',
            'textColor' => '#ffffff',
            'backgroundColor' => '#262325',
            'buttonTextColor' => '#000000',
            'buttonBackgroundColor' => '#ffffff',
            'closeTextColor' => '#000000'
        ],
    ],
    'prioritize_banners_on_same_position' => env('PRIORITIZE_BANNERS_ON_SAME_POSITION', false),
    'one_time_banner_enabled' => env('ONE_TIME_BANNER_ENABLED', true),
    'campaign_debug_key' => env('CAMPAIGN_DEBUG_KEY'),
];

if (file_exists(__DIR__ . '/banners.local.php')) {
    $config = array_replace_recursive($config, require __DIR__ . '/banners.local.php');
}

return $config;
