<?php

namespace Remp\MailerModule\Repository;

use Nette\Database\Table\IRow;
use Remp\MailerModule\Repository;

class ChannelsRepository extends Repository
{
    protected $tableName = 'channels';

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
}
