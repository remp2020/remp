<?php

namespace Remp\MailerModule\Repository;

use Remp\MailerModule\Repository;
use Remp\MailerModule\Selection;

class JobsRepository extends Repository
{
    const STATUS_NEW = 'new';

    protected $tableName = 'mail_jobs';

    protected $dataTableSearchable = [];

    public function add($segment_code, $segment_provider)
    {
        $result = $this->insert([
            'segment_code' => $segment_code,
            'segment_provider' => $segment_provider,
            'status' => static::STATUS_NEW,
            'created_at' => new \DateTime(),
            'updated_at' => new \DateTime(),
        ]);

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
    public function tableFilter($query, $order, $orderDirection, $limit = null, $offset = null)
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

        if ($limit !== null) {
            $selection->limit($limit, $offset);
        }

        return $selection;
    }
}
