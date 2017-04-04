<?php

namespace Remp\MailerModule;

use Nette\Caching\IStorage;
use Nette\Database\Context;
use Nette\Database\Table\IRow;
use Remp\MailerModule\Repository\AuditLogRepository;

class Repository
{
    /** @var Context */
    protected $database;

    /** @var AuditLogRepository */
    protected $auditLogRepository;

    /** @var string */
    protected $tableName = 'undefined';

    /** @var IStorage */
    protected $cacheStorage;

    public function __construct(
        Context $database,
        IStorage $cacheStorage = null
    ) {
        $this->database = $database;
        $this->cacheStorage = $cacheStorage;
    }

    /**
     * @return Selection
     */
    public function getTable()
    {
        return new Selection($this->database, $this->database->getConventions(), $this->tableName, $this->cacheStorage);
    }

    /**
     * @return \Nette\Database\Table\ActiveRow
     */
    public function find($id)
    {
        return $this->getTable()->where(['id' => $id])->fetch();
    }

    /**
     * @return \Nette\Database\Table\ActiveRow
     */
    public function findBy($column, $value)
    {
        return $this->getTable()->where([$column => $value])->fetch();
    }

    public function totalCount()
    {
        return $this->getTable()->count('*');
    }

    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * Update updates provided record with given $data array and mutates the provided instance. Operation is logged
     * to audit log.
     *
     * @param IRow $row
     * @param array $data values to update
     * @return bool
     *
     * @throws \Exception
     */
    public function update(IRow &$row, $data)
    {
        $oldValues = [];
        if ($row instanceof ActiveRow) {
            $oldValues = $row->toArray();
        }

        $res = $this->getTable()->wherePrimary($row->getPrimary())->update($data);
        if (!$res) {
            return false;
        }

        if ($this->auditLogRepository) {
            // filter internal columns
            $data = $this->filterValues((array)$data);

            // filter unchanged columns
            if (!empty($oldValues)) {
                $oldValues = $this->filterValues($oldValues);

                $oldValues = array_intersect_key($oldValues, (array)$data);
                $data = array_diff_assoc((array)$data, $oldValues); // get changed values
                $oldValues = array_intersect_key($oldValues, (array)$data); // get rid of unchanged $oldValues
            }

            $data = [
                'version' => '1',
                'from' => $oldValues,
                'to' => $data,
            ];
            $this->auditLogRepository->add(AuditLogRepository::OPERATION_UPDATE, $this->tableName, $row->getSignature(), $data);
        }

        $row = $this->getTable()->wherePrimary($row->getPrimary())->fetch();
        return true;
    }

    /**
     * Delete deletes provided record from repository and mutates the provided instance. Operation is logged to audit log.
     *
     * @param IRow $row
     * @return bool
     */
    public function delete(IRow &$row)
    {
        $res = $this->getTable()->wherePrimary($row->getPrimary())->delete();
        $oldValues = [];
        if ($row instanceof ActiveRow) {
            $oldValues = $row->toArray();
        }

        if (!$res) {
            return false;
        }

        if ($this->auditLogRepository) {
            $data = [
                'version' => '1',
                'from' => $this->filterValues($oldValues),
                'to' => [],
            ];
            $this->auditLogRepository->add(AuditLogRepository::OPERATION_DELETE, $this->tableName, $row->getSignature(), $data);
        }

        return true;
    }

    /**
     * Insert inserts data to the repository. If single IRow is returned, it attempts to log audit information.
     *
     * @param $data
     * @return bool|int|IRow
     */
    public function insert($data)
    {
        $row = $this->getTable()->insert($data);
        if (!$row instanceof IRow) {
            return $row;
        }

        if ($this->auditLogRepository) {
            $data = [
                'version' => '1',
                'from' => [],
                'to' => $this->filterValues((array)$data),
            ];
            $this->auditLogRepository->add(AuditLogRepository::OPERATION_CREATE, $this->tableName, $row->getSignature(), $data);
        }

        return $row;
    }

    private function filterValues(array $values)
    {
        foreach ($values as $i => $field) {
            if (is_bool($field)) {
                $values[$i] = (int) $field;
            } elseif ($field instanceof \DateTime) {
                $values[$i] = $field->format('Y-m-d H:i:s');
            } elseif (!is_scalar($field)) {
                unset($values[$i]);
            }
        }
        return $values;
    }
}
