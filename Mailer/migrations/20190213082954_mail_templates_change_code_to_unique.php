<?php

use Phinx\Migration\AbstractMigration;

class MailTemplatesChangeCodeToUnique extends AbstractMigration
{
    public function up()
    {
        $this->table('mail_templates')
            ->removeIndex(['code'])
            ->addIndex('code', array('unique' => true))
            ->update();
    }

    public function down()
    {
        $this->table('mail_templates')
            ->removeIndex(['code'])
            ->addIndex('code')
            ->update();
    }
}
