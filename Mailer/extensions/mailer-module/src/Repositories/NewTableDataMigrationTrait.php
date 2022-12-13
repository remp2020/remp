<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repositories;

use Remp\MailerModule\Models\RedisClientFactory;
use Remp\MailerModule\Models\RedisClientTrait;

trait NewTableDataMigrationTrait
{
    use RedisClientTrait;

    protected ?string $newTableName = null;

    protected ?string $newTableDataMigrationIsRunningFlag = null;

    public function setNewTableName(string $table): void
    {
        $this->newTableName = $table;
    }

    public function setNewTableDataMigrationIsRunningFlag(string $flag): void
    {
        $this->newTableDataMigrationIsRunningFlag = $flag;
    }

    public function setRedisClientFactory(RedisClientFactory $redisClientFactory): void
    {
        $this->redisClientFactory = $redisClientFactory;
    }

    public function getNewTable(): Selection
    {
        return new Selection($this->database, $this->database->getConventions(), $this->newTableName, $this->cacheStorage);
    }

    public function newTableDataMigrationIsRunning(): bool
    {
        return (bool) $this->redis()->exists($this->newTableDataMigrationIsRunningFlag);
    }

    public function insert(array $data)
    {
        $result = parent::insert($data);
        if ($this->newTableDataMigrationIsRunning()) {
            $this->getNewTable()->insert($result->toArray());
        }
        return $result;
    }

    public function update(\Nette\Database\Table\ActiveRow $row, array $data): bool
    {
        $result = parent::update($row, $data);
        if ($this->newTableDataMigrationIsRunning()) {
            $this->getNewTable()->where('id', $row->id)->update($data);
        }
        return $result;
    }
}
