<?php
declare(strict_types=1);

namespace Remp\MailerModule\Components\DataTable;

class DataTableFactory
{
    public function create(): DataTable
    {
        return new DataTable();
    }
}
