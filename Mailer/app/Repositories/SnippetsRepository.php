<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repositories;

use Nette\Utils\DateTime;
use Remp\MailerModule\Models\Traits\SlugTrait;

class SnippetsRepository extends Repository
{
    use SlugTrait;

    protected $tableName = 'mail_snippets';

    protected $dataTableSearchable = ['name', 'text', 'html'];

    public function all()
    {
        return $this->getTable()->order('name ASC');
    }

    public function add(string $name, string $code, string $layoutText, string $layoutHtml)
    {
        $this->assertSlug($code);
        $result = $this->insert([
            'name' => $name,
            'code' => $code,
            'created_at' => new DateTime(),
            'updated_at' => new DateTime(),
            'html' => $layoutHtml,
            'text' => $layoutText,
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
}
