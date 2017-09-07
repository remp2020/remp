<?php

namespace Remp\Widgets;

use Arrilot\Widgets\AbstractWidget;
use Ramsey\Uuid\Uuid;

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
        'tableId' => '',
        'rowActions' => [],
        'rowActionLink' => null,
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

        return view("widgets::data_table", [
            'dataSource' => $this->config['dataSource'],
            'cols' => $cols,
            'tableId' => Uuid::getFactory()->uuid4(),
            'rowActions' => $this->config['rowActions'],
            'rowActionLink' => $this->config['rowActionLink'],
        ]);
    }
}