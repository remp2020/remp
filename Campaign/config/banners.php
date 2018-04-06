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
    ],
    'positions' => [
        'top_left' => [
            'name' => 'Top-Left'
        ],
        'top_right' => [
            'name' => 'Top-Right'
        ],
        'bottom_left' => [
            'name' => 'Bottom-Left'
        ],
        'bottom_right' => [
            'name' => 'Bottom-Right'
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
    ]
];
