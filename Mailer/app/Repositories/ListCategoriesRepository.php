<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repositories;

use Nette\Utils\DateTime;
use Remp\MailerModule\Repositories;

class ListCategoriesRepository extends Repository
{
    protected $tableName = 'mail_type_categories';

    public function all()
    {
        return $this->getTable()->order('sorting ASC');
    }

    public function add(string $title, int $sorting)
    {
        return $this->getTable()->insert([
            'title' => $title,
            'sorting' => $sorting,
            'created_at' => new DateTime(),
        ]);
    }
}
