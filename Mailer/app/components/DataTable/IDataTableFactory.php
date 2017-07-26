<?php
namespace Remp\MailerModule\Components;

interface IDataTableFactory
{
    /**
     * @return DataTable
     */
    public function create();
}
