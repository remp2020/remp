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

    protected $tableName = 'mail_jobs';

    protected $dataTableSearchable = [
        ':mail_job_batch_templates.mail_template.name',
    ];

    public function __construct(
        Context $database,
        IStorage $cacheStorage = null
    ) {
        parent::__construct($database, $cacheStorage);
    }

    public function add($segmentCode, $segmentProvider, $context = null)
    {
        $data = [
            'segment_code' => $segmentCode,
            'segment_provider' => $segmentProvider,
            'context' => $context,
            'status' => static::STATUS_NEW,
            'created_at' => new \DateTime(),
            'updated_at' => new \DateTime(),
        ];

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
