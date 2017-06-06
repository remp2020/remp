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

    public function add($name, $display_name, $value, $description, $type)
    {
        $result = $this->insert([
            'name' => $name,
            'display_name' => $display_name,
            'value' => $value,
            'description' => $description,
            'type' => $type,
            'config_category_id' => 4,
            'created_at' => new \DateTime(),
            'updated_at' => new \DateTime(),
        ]);

        if (is_numeric($result)) {
            return $this->getTable()->where('id', $result)->fetch();
        }

        return $result;
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
