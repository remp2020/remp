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

    /** @var array<string, true>|null Column names of $newTableName, resolved once per request. */
    private ?array $newTableColumns = null;

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
        // No migration is configured for this repository at all - don't even reach for Redis,
        // which is only wired up (setRedisClientFactory) for repositories that have one.
        if ($this->newTableDataMigrationIsRunningFlag === null) {
            return false;
        }
        return (bool) $this->redis()->exists($this->newTableDataMigrationIsRunningFlag);
    }

    /**
     * Restricts a payload mirrored into the new table to columns that table actually has.
     *
     * The new table is not necessarily column-identical to the live one: a migration may
     * deliberately drop a column. Without this filter, mirroring a full row
     * (insert() below passes $result->toArray()) fails with "Unknown column ... in
     * 'field list'" on every write for the whole duration of the migration.
     */
    protected function filterToNewTableColumns(array $data): array
    {
        if ($this->newTableColumns === null) {
            // Deliberately not Explorer::getStructure(): the new table is typically created
            // by a migration in the same deploy, so the cached structure either doesn't know
            // about it yet (getColumns() throws) or still describes its pre-migration shape.
            $this->newTableColumns = [];
            foreach ($this->database->query("SHOW COLUMNS FROM `{$this->newTableName}`") as $column) {
                $this->newTableColumns[$column->Field] = true;
            }
        }

        return array_intersect_key($data, $this->newTableColumns);
    }

    public function insert(array $data)
    {
        $result = parent::insert($data);
        if ($this->newTableDataMigrationIsRunning()) {
            $this->getNewTable()->insert($this->filterToNewTableColumns($result->toArray()));
        }
        return $result;
    }

    public function update(\Nette\Database\Table\ActiveRow $row, array $data): bool
    {
        $result = parent::update($row, $data);
        if ($this->newTableDataMigrationIsRunning()) {
            $this->getNewTable()->where('id', $row->id)->update($this->filterToNewTableColumns($data));
        }
        return $result;
    }
}
