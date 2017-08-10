<?php

namespace Remp\MailerModule\Repository;

use Remp\MailerModule\Repository;

class ListCategoriesRepository extends Repository
{
    protected $tableName = 'mail_type_categories';

    public function all()
    {
        return $this->getTable()->order('sorting ASC');
    }
}
