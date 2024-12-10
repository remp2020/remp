<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repositories;

use Nette\Utils\DateTime;

class SourceTemplatesRepository extends Repository
{
    use SoftDeleteTrait;

    protected $tableName = 'mail_source_template';

    protected $dataTableSearchable = ['title'];

    public function all(): Selection
    {
        return $this->getTable()
            ->where('deleted_at', null)
            ->order('sorting ASC');
    }

    public function add(string $title, string $code, string $generator, string $html, string $text, int $sorting = 100): ActiveRow
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

    public function update(\Nette\Database\Table\ActiveRow $row, array $data): bool
    {
        $data['updated_at'] = new \DateTime();
        return parent::update($row, $data);
    }

    public function exists(string $title): int
    {
        return $this->getTable()->where('title', $title)->count('*');
    }

    public function findLast()
    {
        return $this->getTable()->order('sorting DESC')->limit(1);
    }

    public function tableFilter(string $query, ?string $order, ?string $orderDirection, ?int $limit = null, ?int $offset = null): Selection
    {
        $selection = $this->getTable()->where('deleted_at', null);
        if ($order && $orderDirection) {
            $selection->order($order . ' ' . strtoupper($orderDirection));
        } else {
            $selection->order('sorting ASC');
        }

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

    public function getSortingPairs(): array
    {
        return $this->all()
            ->fetchPairs('sorting', 'title');
    }

    public function updateSorting(int $newSorting, ?int $oldSorting = null): void
    {
        if ($newSorting === $oldSorting) {
            return;
        }

        if ($oldSorting !== null) {
            $this->getTable()
                ->where(
                    'sorting > ?',
                    $oldSorting
                )->update(['sorting-=' => 1]);
        }

        $this->getTable()->where(
            'sorting >= ?',
            $newSorting
        )->update(['sorting+=' => 1]);
    }

    public function getByCode(string $code)
    {
        return $this->getTable()->where('code = ?', $code)->fetch();
    }
}
