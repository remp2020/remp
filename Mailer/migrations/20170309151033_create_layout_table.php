<?php

use Phinx\Migration\AbstractMigration;

class CreateLayoutTable extends AbstractMigration
{
    public function change()
    {
        $this->table('layouts')
            ->addColumn('name', 'string')
            ->addTimestamps()
            ->addColumn('layout_text', 'text', array('null' => true))
            ->addColumn('layout_html', 'text', array('null' => true))
            ->save();
    }
}
