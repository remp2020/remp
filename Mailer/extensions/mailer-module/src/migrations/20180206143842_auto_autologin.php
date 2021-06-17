<?php

use Phinx\Migration\AbstractMigration;

class AutoAutologin extends AbstractMigration
{
    public function change()
    {
        $this->table('mail_templates')
            ->changeColumn('autologin', 'boolean', ['null' => false, 'default' => true])
            ->update();
    }
}
