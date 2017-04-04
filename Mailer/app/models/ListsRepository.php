<?php

namespace Remp\MailerModule\Repository;

use Nette\Database\Table\IRow;
use Remp\MailerModule\Repository;

class ListsRepository extends Repository
{
    protected $tableName = 'lists';

    protected $dataTableSearchable = ['code', 'name', 'description'];

    public function all()
    {
        return $this->getTable()->order('name ASC');
    }

    public function add($code, $name, $description, $isConsentRequired, $isLocked, $isPublic)
    {
        $result = $this->insert([
            'code' => $code,
            'name' => $name,
            'description' => $description,
            'is_consent_required' => (bool)$isConsentRequired,
            'is_locked' => (bool)$isLocked,
            'is_public' => (bool)$isPublic,
            'created_at' => new \DateTime(),
        ]);

        if (is_numeric($result)) {
            return $this->getTable()->where('id', $result)->fetch();
        }

        return $result;
    }

    public function update(IRow &$row, $data)
    {
        $params['updated_at'] = new \DateTime();
        return parent::update($row, $data);
    }

    public function tableFilter($query, $order, $orderDirection)
    {
        $selection = $this->getTable()
            ->select('lists.*, count(:list_user_consents.id) AS consents')
            ->order($order . ' ' . strtoupper($orderDirection))
            ->group('lists.id');

        if (!empty($query)) {
            $where = [];
            foreach ($this->dataTableSearchable as $col) {
                $where[$col . ' LIKE ?'] = '%' . $query . '%';
            }

            $selection->whereOr($where);
        }

        return $selection->fetchAll();
    }

    public function isUserSubscribed($user_id, $list)
    {
        /** @TODO implement check */
        return true;
    }
}
