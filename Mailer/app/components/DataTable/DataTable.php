<?php

namespace Remp\MailerModule\DataTable;

use Nette\Application\UI\Control;
use Nette\Utils\Random;

class DataTable extends Control
{
    public function render($colSettings, $rowLink, $rowActions)
    {
        $cols = [];
        array_walk($colSettings, function($item, $key) use (&$cols) {
            if (!is_array($item)) {
                $cols[] = [
                    'name' => $item,
                    'header' => $item,
                ];
            } else {
                $cols[] = array_merge(['name' => $key], $item);
            }
        });

        $presenter = $this->parent;
        $this->template->dataSource = $presenter->link($presenter->action . 'JsonData');
        $this->template->cols = $cols;
        $this->template->rowLink = $presenter->link($rowLink);
        $this->template->rowActions = json_encode($rowActions);
        $this->template->tableId = 'dt-' . Random::generate(6);

        $this->template->setFile(__DIR__ . '/data_table.latte');
        $this->template->render();
    }
}
