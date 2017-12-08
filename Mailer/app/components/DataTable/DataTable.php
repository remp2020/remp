<?php

namespace Remp\MailerModule\Components;

use Nette\Application\UI\Control;
use Nette\Utils\Random;

class DataTable extends Control
{
    private $sourceUrl;
    private $colSettings = [];
    private $tableSettings = [];
    private $rowActions = [];

    /**
     * @param $sourceUrl
     * @return $this
     */
    public function setSourceUrl($sourceUrl)
    {
        $this->sourceUrl = $sourceUrl;

        return $this;
    }

    public function getSourceUrl()
    {
        if ($this->sourceUrl === null) {
            $presenter = $this->getPresenter();
            return $presenter->link($presenter->getAction() . 'JsonData');
        }

        return $this->sourceUrl;
    }

    /**
     * @param $colName
     * @param $colSetting
     * @return $this
     */
    public function setColSetting($colName, $colSetting = null)
    {
        if ($colSetting === null) {
            $colSetting = [
                'header' => $colName,
            ];
        }

        $this->colSettings[$colName] = $colSetting;

        return $this;
    }

    /**
     * @param $colSettingName
     * @param $colSettingValue
     * @return $this
     */
    public function setAllColSetting($colSettingName, $colSettingValue = true)
    {
        foreach ($this->colSettings as $colName => $colSetting) {
            $this->colSettings[$colName][$colSettingName] = $colSettingValue;
        }

        return $this;
    }

    /**
     * @param $tableSettingName
     * @param $tableSetting
     * @return $this
     */
    public function setTableSetting($tableSettingName, $tableSetting = true)
    {
        $this->tableSettings[$tableSettingName] = $tableSetting;

        return $this;
    }

    /**
     * @param $actionName
     * @param $actionClass
     * @return $this
     */
    public function setRowAction($actionName, $actionClass)
    {
        $this->rowActions[] = [
            'name' => $actionName,
            'class' => $actionClass,
        ];

        return $this;
    }

    public function render()
    {
        $this->template->sourceUrl = $this->getSourceUrl();
        $this->template->colSettings = $this->colSettings;
        $this->template->tableSettings = $this->tableSettings;
        $this->template->rowActions = $this->rowActions;

        $this->template->tableId = 'dt-' . Random::generate(6);

        $this->template->setFile(__DIR__ . '/data_table.latte');
        $this->template->render();
    }
}
