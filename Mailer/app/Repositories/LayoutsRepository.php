<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repositories;

use Nette\Utils\DateTime;
use Remp\MailerModule\Repositories\ActiveRow;
use Remp\MailerModule\Repositories;
use Remp\MailerModule\Repositories\Selection;

class LayoutsRepository extends Repository
{
    protected $tableName = 'mail_layouts';

    protected $dataTableSearchable = ['name', 'layout_text', 'layout_html'];

    public function all()
    {
        return $this->getTable()->order('name ASC');
    }

    public function add(string $name, string $layoutText, string $layoutHtml)
    {
        $result = $this->insert([
            'name' => $name,
            'created_at' => new DateTime(),
            'updated_at' => new DateTime(),
            'layout_html' => $layoutHtml,
            'layout_text' => $layoutText,
        ]);

        if (is_numeric($result)) {
            return $this->getTable()->where('id', $result)->fetch();
        }

        return $result;
    }

    public function update(ActiveRow &$row, array $data): bool
    {
        $params['updated_at'] = new DateTime();
        return parent::update($row, $data);
    }

    /**
     * @param string $query
     * @param string $order
     * @param string $orderDirection
     * @param int|null $limit
     * @param int|null $offset
     * @return Selection
     */
    public function tableFilter(string $query, string $order, string $orderDirection, ?int $limit = null, ?int $offset = null)
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


        if ($limit != null) {
            $selection->limit($limit, $offset);
        }

        return $selection;
    }

    public function search(string $term, int $limit): array
    {
        $searchable = ['name'];
        foreach ($searchable as $column) {
            $where[$column . ' LIKE ?'] = '%' . $term . '%';
        }

        $results = $this->all()
            ->select(implode(',', array_merge(['id'], $searchable)))
            ->whereOr($where ?? [])
            ->limit($limit)
            ->fetchAssoc('id');

        return $results ?? [];
    }
}
