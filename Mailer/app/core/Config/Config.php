<?php

namespace Remp\MailerModule\Config;

use Remp\MailerModule\Repository\ConfigsRepository;

class Config
{
    const TYPE_STRING = 'string';
    const TYPE_INT = 'integer';
    const TYPE_TEXT = 'text';
    const TYPE_PASSWORD = 'password';
    const TYPE_HTML = 'html';
    const TYPE_SELECT = 'select';

    private $loaded = false;

    /** @var  ConfigsRepository */
    private $configsRepository;

    /** @var LocalConfig  */
    private $localConfig;

    private $items = null;

    public function __construct(ConfigsRepository $configsRepository, LocalConfig $localConfig)
    {
        $this->configsRepository = $configsRepository;
        $this->localConfig = $localConfig;
    }

    public function get($name)
    {
        if (!$this->loaded) {
            $this->initAutoload();
        }

        if (isset($this->items[$name])) {
            $item = $this->items[$name];
            $value = $item->value;

            if ($this->localConfig->exists($name)) {
                $value = $this->localConfig->value($name);
            }

            return $this->formatValue($value, $item->type);
        }

        $item = $this->configsRepository->loadByName($name);
        if ($item) {
            $value = $item->value;

            if ($this->localConfig->exists($name)) {
                $value = $this->localConfig->value($name);
            }

            return $this->formatValue($value, $item->type);
        }

        // tu mozno bude treba hodit excepnut
        return null;
    }

    private function initAutoload()
    {
        $items = $this->configsRepository->loadAllAutoload();
        foreach ($items as $item) {
            $this->items[$item->name] = $item;
        }
        $this->loaded = true;
    }

    private function formatValue($value, $type = 'string')
    {
        if ($type == self::TYPE_INT) {
            return intval($value);
        }

        return $value;
    }
}
