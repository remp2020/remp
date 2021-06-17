<?php

use Phinx\Migration\AbstractMigration;

class AddExtrasToMailTemplates extends AbstractMigration
{
    public function change()
    {
        $this->table('mail_templates')
            ->addColumn('extras', 'json', ['null' => true])
            ->update();
    }
}
