<?php

use Phinx\Migration\AbstractMigration;

class CreateLayoutTable extends AbstractMigration
{
    public function change()
    {
        $this->table('layouts')
            ->addColumn('name', 'string')
            ->addColumn('created_at', 'datetime')
            ->addColumn('updated_at', 'datetime')
            ->addColumn('layout_text', 'text', array('null' => true))
            ->addColumn('layout_html', 'text', array('null' => true))
            ->save();
    }
}
