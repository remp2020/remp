<?php
declare(strict_types=1);

namespace Remp\MailerModule;

use Nette\Caching\IStorage;
use Nette\Database\Context;
use Nette\Database\IConventions;
use Nette\Database\Table\Selection as NetteSelection;

class Selection extends NetteSelection
{
    /**
     * @inheritdoc
     */
    final public function __construct(Context $context, IConventions $conventions, $tableName, IStorage $cacheStorage = null)
    {
        parent::__construct($context, $conventions, $tableName, $cacheStorage);
    }

    /**
     * @inheritdoc
     */
    public function createSelectionInstance($table = null)
    {
        return new self($this->context, $this->conventions, $table ?: $this->name, $this->cache ? $this->cache->getStorage() : null);
    }

    /**
     * @inheritdoc
     */
    public function createRow(array $row): ActiveRow
    {
        return new ActiveRow($row, $this);
    }
}
