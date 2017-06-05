<?php

namespace Remp\MailerModule\Repository;

use Nette\Database\Table\IRow;
use Remp\MailerModule\Repository;

class ConfigsRepository extends Repository
{
    protected $tableName = 'configs';

    public function all()
    {
        return $this->getTable()->where('config_category_id', 4)->order('sorting ASC');
    }

    public function loadAllAutoload()
    {
        return $this->getTable()->where('autoload', true)->order('sorting');
    }

    public function loadByName($name)
    {
        return $this->getTable()->where('name', $name)->fetch();
    }

    public function update(IRow &$row, $data)
    {
        $data['updated_at'] = new \DateTime();
        return parent::update($row, $data);
    }
}
