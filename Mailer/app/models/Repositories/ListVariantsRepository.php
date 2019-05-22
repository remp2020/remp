<?php

namespace Remp\MailerModule\Repository;

use Nette\Utils\DateTime;
use Remp\MailerModule\Repository;

class ListVariantsRepository extends Repository
{
    protected $tableName = 'mail_type_variants';

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
}
