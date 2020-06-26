<?php

namespace Remp\MailerModule\Repository;

use Nette\Caching\IStorage;
use Nette\Database\Context;
use Nette\Database\Table\IRow;
use Nette\Database\Table\Selection;
use Remp\MailerModule\Repository;

class JobsRepository extends Repository
{
    const STATUS_NEW = 'new';

    protected $tableName = 'mail_jobs';

    protected $dataTableSearchable = [
        ':mail_job_batch_templates.mail_template.name',
    ];

    private $batchesRepository;

    public function __construct(
        Context $database,
        IStorage $cacheStorage = null,
        BatchesRepository $batchesRepository
    ) {
        parent::__construct($database, $cacheStorage);

        $this->batchesRepository = $batchesRepository;
    }

    public function all()
    {
        return $this->getTable()->order('mail_jobs.created_at DESC');
    }

    public function add($segmentCode, $segmentProvider, $context = null, $mailTypeVariant = null)
    {
        $data = [
            'segment_code' => $segmentCode,
            'segment_provider' => $segmentProvider,
            'context' => $context,
            'status' => static::STATUS_NEW,
            'created_at' => new \DateTime(),
            'updated_at' => new \DateTime(),
            'mail_type_variant_id' => $mailTypeVariant ? $mailTypeVariant->id : null
        ];

        return $this->insert($data);
    }

    /**
     * @param $query
     * @param $order
     * @param $orderDirection
     * @param null $limit
     * @param null $offset
     * @return Selection
     */
    public function tableFilter($query, $order, $orderDirection, $listIds = null, $limit = null, $offset = null)
    {
        $selection = $this->getTable()
            ->order($order . ' ' . strtoupper($orderDirection));

        if (!empty($query)) {
            $where = [];
            foreach ($this->dataTableSearchable as $col) {
                $where[$col . ' LIKE ?'] = '%' . $query . '%';
            }

            $selection->whereOr($where);
        }

        if ($listIds) {
            $selection->where([
                ':mail_job_batch_templates.mail_template.mail_type_id' => $listIds,
            ]);
        }

        if ($limit !== null) {
            $selection->limit($limit, $offset);
        }

        return $selection;
    }

    public function update(IRow &$row, $data)
    {
        $this->getDatabase()->beginTransaction();

        if (!$this->isEditable($row->id)) {
            $this->getDatabase()->rollBack();
            throw new \Exception("Job can't be updated. One or more Mail Job Batches were already started.");
        }

        $result = parent::update($row, $data);

        $this->getDatabase()->commit();
        return $result;
    }

    public function isEditable($jobId)
    {
        if ($this->batchesRepository->notEditableBatches($jobId)->count() > 0) {
            return false;
        }
        return true;
    }

    public function search(string $term, int $limit): Selection
    {
        foreach ($this->dataTableSearchable as $column) {
            $where[$column . ' LIKE ?'] = '%' . $term . '%';
        }

        return $this->all()
            ->whereOr($where ?? [])
            ->order('created_at DESC')
            ->limit($limit);
    }
}
