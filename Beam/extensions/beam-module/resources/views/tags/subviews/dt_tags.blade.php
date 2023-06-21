{!! Widget::run('DataTable', [
            'colSettings' => [
                'name' => [
                    'header' => 'tag',
                    'orderable' => false,
                    'filter' => $tags,
                    'priority' => 1,
                    'render' => 'link',
                ],
                'articles_count' => [
                    'header' => 'articles',
                    'priority' => 3,
                    'orderSequence' => ['desc', 'asc'],
                    'searchable' => false,
                    'render' => 'number',
                    'className' => 'text-right'
                ],
                'conversions_count' => [
                    'header' => 'conversions',
                    'priority' => 2,
                    'orderSequence' => ['desc', 'asc'],
                    'searchable' => false,
                    'render' => 'number',
                    'className' => 'text-right'
                ],
                'conversions_amount' => [
                    'header' => 'amount',
                    'render' => 'array',
                    'priority' => 2,
                    'orderSequence' => ['desc', 'asc'],
                    'searchable' => false,
                    'className' => 'text-right'
                ],
                'pageviews_all' => [
                    'header' => 'all pageviews',
                    'render' => 'number',
                    'priority' => 2,
                    'orderSequence' => ['desc', 'asc'],
                    'searchable' => false,
                    'className' => 'text-right'
                ],
                'pageviews_not_subscribed' => [
                    'header' => 'not subscribed pageviews',
                    'render' => 'number',
                    'priority' => 5,
                    'orderSequence' => ['desc', 'asc'],
                    'searchable' => false,
                    'className' => 'text-right'
                ],
                'pageviews_subscribers' => [
                    'header' => 'subscriber pageviews',
                    'render' => 'number',
                    'priority' => 5,
                    'orderSequence' => ['desc', 'asc'],
                    'searchable' => false,
                    'className' => 'text-right'
                ],
                'avg_timespent_all' => [
                    'header' => 'avg time all',
                    'render' => 'duration',
                    'priority' => 2,
                    'orderSequence' => ['desc', 'asc'],
                    'searchable' => false,
                    'className' => 'text-right'
                ],
                'avg_timespent_not_subscribed' => [
                    'header' => 'avg time not subscribed',
                    'render' => 'duration',
                    'priority' => 5,
                    'orderSequence' => ['desc', 'asc'],
                    'searchable' => false,
                    'className' => 'text-right'
                ],
                'avg_timespent_subscribers' => [
                    'header' => 'avg time subscribers',
                    'render' => 'duration',
                    'priority' => 5,
                    'orderSequence' => ['desc', 'asc'],
                    'searchable' => false,
                    'className' => 'text-right'
                ],
            ],
            'dataSource' => $dataSource,
            'order' => [2, 'desc'],
            'requestParams' => [
                'published_from' => '$(\'[name="published_from"]\').val()',
                'published_to' => '$(\'[name="published_to"]\').val()',
                'conversion_from' => '$(\'[name="conversion_from"]\').val()',
                'conversion_to' => '$(\'[name="conversion_to"]\').val()',
                'content_type' => '$(\'[name="content_type"]\').val()',
                'tz' => 'Intl.DateTimeFormat().resolvedOptions().timeZone'
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
                [
                    'event' => 'change',
                    'selector' => '[name="conversion_from"]'
                ],
                [
                    'event' => 'change',
                    'selector' => '[name="conversion_to"]',
                ],
                [
                    'event' => 'change',
                    'selector' => '[name="content_type"]',
                ],
            ],
            'exportColumns' => [0,1,2,3,4,5,6,7,8,9],
        ]) !!}
