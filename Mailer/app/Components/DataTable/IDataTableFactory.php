<?php
declare(strict_types=1);
namespace Remp\MailerModule\Components\DataTable;

interface IDataTableFactory
{
    /**
     * @return DataTable
     */
    public function create(): DataTable;
}
