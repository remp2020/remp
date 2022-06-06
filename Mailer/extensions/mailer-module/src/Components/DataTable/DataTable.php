<?php
declare(strict_types=1);

namespace Remp\MailerModule\Components\DataTable;

use Nette\Application\UI\Control;
use Nette\Utils\Json;

class DataTable extends Control
{
    private $sourceUrl;
    private $colSettings = [];
    private $tableSettings = [];
    private $rowActions = [];

    public function setSourceUrl(string $sourceUrl): self
    {
        $this->sourceUrl = $sourceUrl;

        return $this;
    }

    public function getSourceUrl(): string
    {
        if ($this->sourceUrl === null) {
            $presenter = $this->getPresenter();
            return $presenter->link($presenter->getAction() . 'JsonData');
        }

        return $this->sourceUrl;
    }

    public function setColSetting(string $colName, array $colSetting): self
    {
        if (!array_key_exists('priority', $colSetting)) {
            throw new DataTableException('Missing "priority" item in DataTable configuration array for column: "' . $colName . '"');
        }

        $this->colSettings[$colName] = $colSetting;
        return $this;
    }

    public function setAllColSetting(string $colSettingName, bool $colSettingValue = true): self
    {
        foreach ($this->colSettings as $colName => $colSetting) {
            $this->colSettings[$colName][$colSettingName] = $colSettingValue;
        }

        return $this;
    }

    public function setTableSetting(string $tableSettingName, $tableSetting = null): self
    {
        $this->tableSettings[$tableSettingName] = $tableSetting;

        return $this;
    }

    public function setRowAction(string $actionName, string $actionClass, string $actionTitle, array $htmlAttributes = []): self
    {
        $action = [
            'name' => $actionName,
            'class' => $actionClass,
            'title' => $actionTitle
        ];
        $action = array_merge($action, $htmlAttributes);

        $this->rowActions[] = $action;

        return $this;
    }

    public function render(): void
    {
        $this->template->sourceUrl = $this->getSourceUrl();
        $this->template->colSettings = $this->colSettings;
        $this->template->tableSettings = $this->tableSettings;
        $this->template->rowActions = $this->rowActions;

        foreach ($this->template->colSettings as $colName => $colSetting) {
            $this->template->colSettings[$colName] = array_merge([
                'colIndex' => array_search(
                    $colName,
                    array_keys($this->template->colSettings)
                )
            ], $this->template->colSettings[$colName]);
        }

        $this->template->tableId = 'dt-' . hash("crc32c", Json::encode([
            $this->template->sourceUrl,
            $this->template->colSettings,
            $this->template->tableSettings,
            $this->template->rowActions,
        ]));

        $this->template->setFile(__DIR__ . '/data_table.latte');
        $this->template->render();
    }
}
