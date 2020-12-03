<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repository;

use Nette\Caching\IStorage;
use Nette\Database\Context;
use Nette\Database\Table\Selection;
use Remp\MailerModule\Repository;
use Nette\Utils\DateTime;

class SourceTemplatesRepository extends Repository
{
    protected $tableName = 'mail_source_template';

    protected $dataTableSearchable = ['title'];

    public function all()
    {
        return $this->getTable()->select('*')->order('sorting DESC');
    }

    public function add($title, $code, $generator, $html, $text, $sorting = 100)
    {
        return $this->insert([
            'title' => $title,
            'code' => $code,
            'generator' => $generator,
            'content_html' => $html,
            'content_text' => $text,
            'sorting' => $sorting,
            'created_at' => new DateTime(),
            'updated_at' => new DateTime(),
        ]);
    }

    public function exists(string $title): int
    {
        return $this->getTable()->where('title', $title)->count('*');
    }

    public function findLast()
    {
        return $this->getTable()->order('sorting DESC')->limit(1);
    }

    public function tableFilter(string $query, string $order, string $orderDirection, ?int $limit = null, ?int $offset = null): Selection
    {
        $selection = $this->getTable()
            ->order($order . ' ' . strtoupper($orderDirection));

        if (!empty($query)) {
            $where = [];
            foreach ($this->dataTableSearchable as $col) {
                $where[$col . ' LIKE ?'] = '%' . $query . '%';
            }

            $selection->whereOr($where);
        }

        if ($limit !== null) {
            $selection->limit($limit, $offset);
        }

        return $selection;
    }
}
