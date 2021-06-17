<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repositories;

use Nette\Utils\DateTime;

class ListVariantsRepository extends Repository
{
    protected $tableName = 'mail_type_variants';

    protected $dataTableSearchable = ['code', 'title'];

    public function add(ActiveRow $mailType, string $title, string $code, int $sorting)
    {
        return $this->insert([
            'mail_type_id' => $mailType->id,
            'title' => $title,
            'code' => $code,
            'sorting' => $sorting,
            'created_at' => new DateTime()
        ]);
    }

    public function findByIdAndMailTypeId(int $id, int $mailTypeId): ?ActiveRow
    {
        return $this->getTable()->where(['id' => $id, 'mail_type_id' => $mailTypeId])->fetch();
    }

    public function tableFilter(string $query, string $order, string $orderDirection, ?array $listIds = null, ?int $limit = null, ?int $offset = null): Selection
    {
        $selection = $this->getTable()
            ->select('mail_type_variants.*, COUNT(:mail_user_subscription_variants.id) AS count')
            ->group('mail_type_variants.id');

        if ($order === 'count') {
            $selection->order('COUNT(*) DESC');
        } else {
            $selection->order($order . ' ' . strtoupper($orderDirection));
        }

        if (!empty($query)) {
            $where = [];
            foreach ($this->dataTableSearchable as $col) {
                $where[$col . ' LIKE ?'] = '%' . $query . '%';
            }
            $selection->whereOr($where);
        }

        if ($listIds !== null) {
            $selection->where([
                'mail_type_id' => $listIds,
            ]);
        }

        if ($limit !== null) {
            $selection->limit($limit, $offset);
        }

        return $selection;
    }
}
