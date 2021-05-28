<?php
declare(strict_types=1);

namespace Remp\MailerModule\Components\DataTable;

class DataTableFactory
{
    public function create(): DataTable
    {
        return new DataTable();
    }

    public function getOrderFromRequest(array $params): array
    {
        if (!isset($params['order'])) {
            return [null, null];
        }

        return [
            $params['columns'][$params['order'][0]['column']]['name'],
            $params['order'][0]['dir'],
        ];
    }
}
