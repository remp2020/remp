<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repositories;

use Nette;
use Nette\Caching\Storage;
use Nette\Database\Conventions;
use Nette\Database\Explorer;
use Nette\Database\Table\Selection as NetteSelection;

class Selection extends NetteSelection
{
    use DateFieldsProcessorTrait;

    /**
     * @inheritdoc
     */
    public function __construct(
        Explorer $explorer,
        Conventions $conventions,
        string $tableName,
        Storage $cacheStorage = null
    ) {
        parent::__construct($explorer, $conventions, $tableName, $cacheStorage);
    }

    public function createSelectionInstance(string $table = null): NetteSelection
    {
        return new self(
            $this->context,
            $this->conventions,
            $table ?: $this->name,
            $this->cache ? $this->cache->getStorage() : null
        );
    }

    public function createRow(array $row): Nette\Database\Table\ActiveRow
    {
        return new ActiveRow($row, $this);
    }

    public function condition($condition, array $params, $tableChain = null): void
    {
        $params = $this->processDateFields($params);
        parent::condition($condition, $params, $tableChain);
    }

    public function insert(iterable $data): ActiveRow
    {
        return parent::insert($data);
    }

    // not sure if we need this
    public function insertMulti(iterable $data): int
    {
        return parent::insert($data);
    }
}
