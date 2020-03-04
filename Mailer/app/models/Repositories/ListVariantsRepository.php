<?php

namespace Remp\MailerModule\Repository;

use Nette\Utils\DateTime;
use Remp\MailerModule\Repository;

class ListVariantsRepository extends Repository
{
    protected $tableName = 'mail_type_variants';

    protected $dataTableSearchable = ['code', 'title'];

    public function add($mailType, $title, $code, $sorting)
    {
        return $this->insert([
            'mail_type_id' => $mailType->id,
            'title' => $title,
            'code' => $code,
            'sorting' => $sorting,
            'created_at' => new DateTime()
        ]);
    }

    public function findByIdAndMailTypeId(int $id, int $mailTypeID)
    {
        return $this->getTable()->where(['id' => $id, 'mail_type_id' => $mailTypeID])->fetch();
    }

    public function tableFilter($query, $order, $orderDirection, $listId = null, $limit = null, $offset = null)
    {
        $selection = $this->getTable()
            ->select('mail_type_variants.*, COUNT(:mail_user_subscription_variants.id) AS count')
            ->group('mail_type_variants.id');

        if ($order === 'count') {
            $selection->order('COUNT(*) DESC');
        } else {
            $selection->order($order . ' ' . strtoupper($orderDirection));
        }

        if (!empty($query)) {
            $where = [];
            foreach ($this->dataTableSearchable as $col) {
                $where[$col . ' LIKE ?'] = '%' . $query . '%';
            }
            $selection->whereOr($where);
        }

        if ($listId !== null) {
            $selection->where([
                'mail_type_id' => $listId,
            ]);
        }

        if ($limit !== null) {
            $selection->limit($limit, $offset);
        }

        return $selection;
    }
}
