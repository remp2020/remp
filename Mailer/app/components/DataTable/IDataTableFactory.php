<?php
namespace Remp\MailerModule\DataTable;

interface IDataTableFactory
{
    /**
     * @return DataTable
     */
    public function create();
}