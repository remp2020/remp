<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repositories;

use Exception;
use Nette\Caching\Storage;
use Nette\Database\Explorer;
use Nette\Utils\DateTime;
use Remp\MailerModule\Models\Job\JobSegmentsManager;

class JobsRepository extends Repository
{
    const STATUS_NEW = 'new';

    protected $tableName = 'mail_jobs';

    protected $dataTableSearchable = [
        ':mail_job_batch_templates.mail_template.name',
    ];

    public function __construct(
        Explorer $database,
        private BatchesRepository $batchesRepository,
        Storage $cacheStorage = null
    ) {
        parent::__construct($database, $cacheStorage);
    }

    public function all(): Selection
    {
        return $this->getTable()->order('mail_jobs.created_at DESC');
    }

    public function add(JobSegmentsManager $jobSegmentsManager, ?string $context = null, ?ActiveRow $mailTypeVariant = null): ?ActiveRow
    {
        $data = [
            'segments' => $jobSegmentsManager->toJson(),
            'context' => $context,
            'status' => static::STATUS_NEW,
            'created_at' => new DateTime(),
            'updated_at' => new DateTime(),
            'mail_type_variant_id' => $mailTypeVariant->id ?? null
        ];

        return $this->insert($data);
    }

    public function tableFilter(string $query, string $order, string $orderDirection, array $listIds = [], ?int $limit = null, ?int $offset = null): Selection
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

    public function update(\Nette\Database\Table\ActiveRow $row, array $data): bool
    {
        $this->getDatabase()->beginTransaction();

        if (!$this->isEditable($row->id)) {
            $this->getDatabase()->rollBack();
            throw new Exception("Job can't be updated. One or more Mail Job Batches were already started.");
        }

        $data['updated_at'] = new \DateTime();
        $result = parent::update($row, $data);

        $this->getDatabase()->commit();
        return $result;
    }

    public function isEditable(int $jobId): bool
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
