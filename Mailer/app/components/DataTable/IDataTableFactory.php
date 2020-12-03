<?php
declare(strict_types=1);
namespace Remp\MailerModule\Components;

interface IDataTableFactory
{
    /**
     * @return DataTable
     */
    public function create(): DataTable;
}
