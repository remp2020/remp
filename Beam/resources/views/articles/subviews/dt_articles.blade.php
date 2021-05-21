{!! Widget::run('DataTable', [
                'colSettings' => [
                    'title' => [
                        'orderable' => false,
                        'priority' => 1,
                    ],
                    'pageviews_all' => [
                        'header' => 'all pageviews',
                        'render' => 'number',
                        'priority' => 2,
                    ],
                    'pageviews_signed_in' => [
                        'header' => 'signed in pageviews',
                        'render' => 'number',
                        'priority' => 3,
                    ],
                    'pageviews_subscribers' => [
                        'header' => 'subscriber pageviews',
                        'render' => 'number',
                        'priority' => 3,
                    ],
                    'avg_timespent_all' => [
                        'header' => 'avg time all',
                        'render' => 'duration',
                        'priority' => 2,
                    ],
                    'avg_timespent_signed_in' => [
                        'header' => 'avg time signed in',
                        'render' => 'duration',
                        'priority' => 3,
                    ],
                    'avg_timespent_subscribers' => [
                        'header' => 'avg time subscribers',
                        'render' => 'duration',
                        'priority' => 3,
                    ],
                    'conversions_count' => [
                        'header' => 'conversions',
                        'render' => 'number',
                        'priority' => 2,
                    ],
                    'conversions_sum' => [
                        'header' => 'amount',
                        'render' => 'array',
                        'priority' => 2,
                    ],
                    'conversions_avg' => [
                        'header' => 'avg amount',
                        'render' => 'array',
                        'priority' => 3,
                    ],
                    'content_type' => [
                        'header' => 'Type',
                        'orderable' => false,
                        'filter' => $contentTypes,
                        'priority' => 2,
                        'visible' => count($contentTypes) > 1
                    ],
                    'sections[, ].name' => [
                        'header' => 'sections',
                        'orderable' => false,
                        'filter' => $sections,
                        'priority' => 4,
                    ],
                    'authors[, ].name' => [
                        'header' => 'authors',
                        'orderable' => false,
                        'filter' => $authors,
                        'priority' => 5,
                    ],
                    'published_at' => [
                        'header' => 'published',
                        'render' => 'date',
                        'priority' => 5,
                    ],
                ],
                'dataSource' => $dataSource,
                'order' => [7, 'desc'],
                'requestParams' => [
                    'published_from' => '$(\'[name="published_from"]\').val()',
                    'published_to' => '$(\'[name="published_to"]\').val()'
                ],
                'refreshTriggers' => [
                    [
                        'event' => 'change',
                        'selector' => '[name="published_from"]'
                    ],
                    [
                        'event' => 'change',
                        'selector' => '[name="published_to"]',
                    ],
                ],
            ]) !!}
