<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repositories;

use Nette\Utils\DateTime;

class LayoutsRepository extends Repository
{
    use SoftDeleteTrait;

    protected $tableName = 'mail_layouts';

    protected $dataTableSearchable = ['name', 'code', 'layout_text', 'layout_html'];

    public function all(): Selection
    {
        return $this->getTable()
            ->where('deleted_at', null)
            ->order('name ASC');
    }

    public function add(string $name, string $code, string $layoutText, string $layoutHtml): ActiveRow
    {
        $result = $this->insert([
            'name' => $name,
            'code' => $code,
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

    public function update(\Nette\Database\Table\ActiveRow $row, array $data): bool
    {
        $data['updated_at'] = new DateTime();
        return parent::update($row, $data);
    }

    public function tableFilter(string $query, string $order, string $orderDirection, ?int $limit = null, ?int $offset = null): Selection
    {
        $selection = $this->getTable()
            ->where('deleted_at', null)
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
            ->whereOr($where)
            ->limit($limit)
            ->fetchAssoc('id');

        return $results;
    }

    public function canBeDeleted(ActiveRow $layout): bool
    {
        return !(bool) $layout->related('mail_templates')->where('deleted_at', null)->count('*');
    }
}
