<?php

use Phinx\Migration\AbstractMigration;

class AddCodeToMailSourceTemplate extends AbstractMigration
{
    public function change()
    {
        $this->table('mail_source_template')
            ->addColumn('code', 'string', ['null' => true, 'after' => 'title'])
            ->update();
    }
}
