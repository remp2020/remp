<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repositories;

class MailTypesRepository extends Repository
{
    protected $tableName = 'mail_types';

    public function all(): Selection
    {
        return $this->getTable();
    }
}
