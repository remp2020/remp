<?php

namespace Remp\MailerModule\Repository;

use Remp\MailerModule\Repository;
use Remp\MailerModule\Selection;

class SegmentsRepository extends Repository
{
    protected $tableName = 'segments';

    public function all()
    {
        return $this->getTable()->order('name ASC');
    }
}
