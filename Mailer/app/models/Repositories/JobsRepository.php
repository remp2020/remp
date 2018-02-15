<?php

namespace Remp\MailerModule\Repository;

use Nette\Caching\IStorage;
use Nette\Database\Context;
use Nette\Database\Table\Selection;
use Remp\MailerModule\Repository;
use Remp\MailerModule\Segment\Crm;

class JobsRepository extends Repository
{
    const STATUS_NEW = 'new';

    /** @var SegmentsRepository */
    protected $segmentsRepository;

    protected $tableName = 'mail_jobs';

    protected $dataTableSearchable = [
        ':mail_job_batch_templates.mail_template.name',
    ];

    public function __construct(
        Context $database,
        IStorage $cacheStorage = null,
        SegmentsRepository $segmentsRepository
    ) {
        parent::__construct($database, $cacheStorage);

        $this->segmentsRepository = $segmentsRepository;
    }

    public function add($segment_code, $segment_provider, $context = null)
    {
        $data = [
            'segment_code' => $segment_code,
            'segment_provider' => $segment_provider,
            'context' => $context,
            'status' => static::STATUS_NEW,
            'created_at' => new \DateTime(),
            'updated_at' => new \DateTime(),
        ];

        // remove segment_id after CRM and REMP separation
        if ($segment_provider == Crm::PROVIDER_ALIAS) {
            $segment = $this->segmentsRepository->findBy('code', $segment_code);
            $data['segment_id'] = $segment->id;
        }

        $result = $this->insert($data);

        if (is_numeric($result)) {
            return $this->getTable()->where('id', $result)->fetch();
        }

        return $result;
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
}
