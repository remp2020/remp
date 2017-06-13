<?php

namespace Remp\MailerModule\Repository;

use Nette\Database\Table\IRow;
use Remp\MailerModule\Repository;
use Remp\MailerModule\Selection;

class ListsRepository extends Repository
{
    protected $tableName = 'mail_types';

    protected $dataTableSearchable = ['code', 'title', 'description'];

    public function all()
    {
        return $this->getTable()->order('sorting ASC');
    }

    public function add($code, $name, $description, $order, $isConsentRequired, $isLocked, $isPublic)
    {
        $this->updateOrder(null, $order);

        $result = $this->insert([
            'code' => $code,
            'title' => $name,
            'description' => $description,
            'sorting' => $order,
            'auto_subscribe' => !(bool)$isConsentRequired,
            'locked' => (bool)$isLocked,
            'is_public' => (bool)$isPublic,
            // 'created_at' => new \DateTime(),
        ]);

        if (is_numeric($result)) {
            return $this->getTable()->where('id', $result)->fetch();
        }

        return $result;
    }

    public function update(IRow &$row, $data)
    {
        $this->updateOrder($row->sorting, $data['order']);

        $params['updated_at'] = new \DateTime();
        return parent::update($row, $data);
    }

    public function updateOrder($oldOrder, $newOrder)
    {
        if ($oldOrder == $newOrder) {
            return;
        }

        if ($oldOrder !== null) {
            $this->getTable()->where('sorting > ?', $oldOrder)->update(['errors_count-=' => 1]);
        }

        $this->getTable()->where('sorting > ?', $newOrder)->update(['errors_count+=' => 1]);
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
            ->select('mail_types.*, count(:mail_user_preferences.id) AS consents')
            ->order($order . ' ' . strtoupper($orderDirection))
            ->group('mail_types.id');

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
