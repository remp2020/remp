<?php

return [
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
    'prioritize_banners_on_same_position' => env('PRIORITIZE_BANNERS_ON_SAME_POSITION', false)
];
