<?php

namespace Remp\MailerModule\Repository;

use Remp\MailerModule\Repository;

class MailTypesRepository extends Repository
{
    protected $tableName = 'mail_types';

    public function all()
    {
        return $this->getTable();
    }
}
