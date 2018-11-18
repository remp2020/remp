<?php

namespace Remp\MailerModule\Repository;

use Nette\Utils\DateTime;
use Remp\MailerModule\Repository;

class ListVariantsRepository extends Repository
{
    protected $tableName = 'mail_type_variants';

    public function add($mailType, $title, $code, $sorting)
    {
        $result = $this->insert([
            'mail_type_id' => $mailType->id,
            'title' => $title,
            'code' => $code,
            'sorting' => $sorting,
            'created_at' => new DateTime()
        ]);

        if (is_numeric($result)) {
            return $this->getTable()->where('id', $result)->fetch();
        }

        return $result;
    }
}
