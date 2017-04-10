<?php

namespace App\Widgets;

use Arrilot\Widgets\AbstractWidget;
use Psy\Util\Json;
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
        'rowLink' => '',
        'tableId' => '',
    ];

    /**
     * Treat this method as a controller action.
     * Return view() or other content to display.
     */
    public function run()
    {
        $cols = [];
        array_walk($this->config['colSettings'], function($item, $key) use (&$cols) {
            if (!is_array($item)) {
                $cols[] = [
                    'name' => $item,
                    'header' => $item,
                ];
            } else {
                $cols[] = array_merge(['name' => $key], $item);
            }
        });

        return view("widgets.data_table", [
            'dataSource' => $this->config['dataSource'],
            'cols' => $cols,
            'rowLink' => $this->config['rowLink'],
            'tableId' => Uuid::getFactory()->uuid4(),
        ]);
    }
}