<?php

namespace Remp\Widgets;

use Arrilot\Widgets\AbstractWidget;
use Psy\Util\Json;

/**
 * Class DataTable
 *
 * Usage example
 *
 *  {!! Widget::run('DataTable', [
 *      'colSettings' => [
 *          'title',
 *          'pageview_sum' => ['header' => 'pageviews'],
 *          'timespent_sum' => ['header' => 'total time read', 'render' => 'duration'],
 *          'avg_sum' => ['header' => 'avg', 'render' => 'duration'],
 *          'authors[, ].name' => ['header' => 'authors', 'orderable' => false, 'filter' => $authors],
 *          'sections[, ].name' => ['header' => 'sections', 'orderable' => false, 'filter' => $sections],
 *          'published_at' => ['header' => 'published at', 'render' => 'date'],
 *      ],
 *      'dataSource' => route('articles.dtPageviews'),
 *      'displaySearchAndPaging' => true,
 *      'order' => [4, 'desc'],
 *      'requestParams' => [
 *          'published_from' => '$("[name=\"published_from\"]").data("DateTimePicker").date().set({hour:0,minute:0,second:0,millisecond:0}).toISOString()',
 *          'published_to' => '$("[name=\"published_to\"]").data("DateTimePicker").date().set({hour:23,minute:59,second:59,millisecond:999}).toISOString()',
 *      ],
 *      'refreshTriggers' => [
 *          [
 *              'event' => 'dp.change',
 *              'selector' => '[name="published_from"]'
 *          ],
 *          [
 *              'event' => 'dp.change',
 *              'selector' => '[name="published_to"]',
 *          ]
 *      ],
 *  ]) !!}
 *
 * @package Remp\Widgets
 */
class DataTable extends AbstractWidget
{
    /**
     * The configuration array.
     *
     * @var array
     */
    protected $config = [
        'dataSource' => '',
        'colSettings' => [],
        'order' => [],
        'tableId' => '',
        'rowActions' => [],
        'rowHighlights' => [], // column-value conditions that have to be met to highlight the row
        'requestParams' => [], // extra request parameters attached to JSON dataSource request
        'refreshTriggers' => [], // external triggers that should execute datatable ajax reload
        'displaySearchAndPaging' => true, // display header with search & pagination
    ];

    /**
     * Treat this method as a controller action.
     * Return view() or other content to display.
     */
    public function run()
    {
        $cols = [];
        array_walk($this->config['colSettings'], function ($item, $key) use (&$cols) {
            if (!is_array($item)) {
                $cols[] = [
                    'name' => $item,
                    'header' => $item,
                ];
            } else {
                $cols[] = array_merge(['name' => $key], $item);
            }
        });

        $tableId = md5(Json::encode([
            $this->config['dataSource'],
            $cols,
            $this->config['rowActions'],
        ]));

        return view("widgets::data_table", [
            'dataSource' => $this->config['dataSource'],
            'cols' => $cols,
            'tableId' => $tableId,
            'rowActions' => $this->config['rowActions'],
            'rowHighlights' => $this->config['rowHighlights'],
            'order' => $this->config['order'],
            'requestParams' => $this->config['requestParams'],
            'refreshTriggers' => $this->config['refreshTriggers'],
            'displaySearchAndPaging' => $this->config['displaySearchAndPaging'],
        ]);
    }
}
