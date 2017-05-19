<?php

namespace Remp\MailerModule\Repository;

use Nette\Database\Table\IRow;
use Remp\MailerModule\Repository;

class LayoutsRepository extends Repository
{
    protected $tableName = 'mail_layouts';

    protected $dataTableSearchable = ['name', 'layout_text', 'layout_html'];

    public function all()
    {
        return $this->getTable()->order('name ASC');
    }

    public function add($name, $layoutText, $layoutHtml)
    {
        $result = $this->insert([
            'name' => $name,
            'created_at' => new \DateTime(),
            'layout_html' => $layoutHtml,
            'layout_text' => $layoutText,
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
            ->order($order . ' ' . strtoupper($orderDirection));

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
