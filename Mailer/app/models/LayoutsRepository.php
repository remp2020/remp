<?php

namespace Remp\MailerModule\Repository;

use Nette\Database\Table\IRow;
use Remp\MailerModule\Repository;

class LayoutsRepository extends Repository
{
    protected $tableName = 'layouts';

    public function exists($name)
    {
        return $this->getTable()->where('name', $name)->count('*') > 0;
    }

    public function findByName($name)
    {
        return $this->getTable()->where('name', $name)->fetch();
    }

    public function all()
    {
        return $this->getTable()->order('name ASC');
    }

    public function add($name, $layoutText, $layoutHtml)
    {
        $result = $this->insert([
            'name' => $name,
            'created_at' => new \DateTime(),
            'updated_at' => new \DateTime(),
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
}
