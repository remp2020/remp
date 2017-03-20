<?php

namespace Remp\MailerModule\Repository;

use Nette\Database\Table\IRow;
use Remp\MailerModule\Repository;

class NewslettersRepository extends Repository
{
    protected $tableName = 'newsletters';

    protected $dataTableSearchable = ['name'];

    public function all()
    {
        return $this->getTable()->order('name ASC');
    }

    public function add($name, $consentRequired)
    {
        $result = $this->insert([
            'name' => $name,
            'created_at' => new \DateTime(),
            'consent_required' => (bool)$consentRequired,
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
            ->select('newsletters.*, count(:newsletter_consents.id) AS subscribers')
            ->order($order . ' ' . strtoupper($orderDirection))
            ->group('newsletters.id');

        if (!empty($query)) {
            $where = [];
            foreach ($this->dataTableSearchable as $col) {
                $where[$col . ' LIKE ?'] = '%' . $query . '%';
            }

            $selection->whereOr($where);
        }

        return $selection->fetchAll();
    }
}
