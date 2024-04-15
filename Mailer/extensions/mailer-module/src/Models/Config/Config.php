<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Config;

use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Remp\MailerModule\Repositories\ConfigsRepository;

class Config
{
    public const TYPE_STRING = 'string';
    public const TYPE_INT = 'integer';
    public const TYPE_TEXT = 'text';
    public const TYPE_PASSWORD = 'password';
    public const TYPE_HTML = 'html';
    public const TYPE_SELECT = 'select';
    public const TYPE_BOOLEAN = 'boolean';

    private int $cacheExpirationInSeconds = 60;
    private int $lastConfigRefreshTimestamp = 0;

    private bool $allowLocalConfigFallback = false;

    private ?array $items = null;

    public function __construct(
        private readonly ConfigsRepository $configsRepository,
        private readonly LocalConfig $localConfig,
        private readonly Storage $cacheStorage,
    ) {
    }

    public function allowLocalConfigFallback(bool $allow = true): void
    {
        $this->allowLocalConfigFallback = $allow;
    }

    public function get(string $name)
    {
        if ($this->needsRefresh()) {
            $this->refresh();
        }

        if (isset($this->items[$name])) {
            $item = $this->items[$name];
            $value = $this->localConfig->exists($name)
                ? $this->localConfig->value($name)
                : $item->value;

            return $this->formatValue($value, $item->type);
        }

        if ($this->allowLocalConfigFallback && $this->localConfig->exists($name)) {
            return $this->localConfig->value($name);
        }

        throw new ConfigNotExistsException("Setting {$name} does not exists.");
    }

    public function refresh(bool $force = false): void
    {
        $cacheData = $this->cacheStorage->read('application_config_cache');
        if (!$force && $cacheData) {
            $this->items = $cacheData;
        } else {
            $items = $this->configsRepository->all();
            foreach ($items as $item) {
                $this->items[$item->name] = (object)$item->toArray();
            }
            $this->cacheStorage->write('application_config_cache', $this->items, [
                Cache::Expire => $this->cacheExpirationInSeconds
            ]);
        }
        $this->lastConfigRefreshTimestamp = time();
    }

    private function needsRefresh(): bool
    {
        $refreshAt = $this->lastConfigRefreshTimestamp + $this->cacheExpirationInSeconds;
        return time() > $refreshAt;
    }

    private function formatValue($value, $type = 'string')
    {
        if ($type == self::TYPE_INT) {
            return (int)$value;
        }

        return $value;
    }
}
