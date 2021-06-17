<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repositories;

use Nette\Database\Table\Selection;
use Nette\Utils\DateTime;

class ListCategoriesRepository extends Repository
{
    protected $tableName = 'mail_type_categories';

    public function all(): Selection
    {
        return $this->getTable()->order('sorting ASC');
    }

    public function add(string $title, int $sorting): ActiveRow
    {
        return $this->getTable()->insert([
            'title' => $title,
            'sorting' => $sorting,
            'created_at' => new DateTime(),
        ]);
    }
}
