<?php

use Phinx\Migration\AbstractMigration;

class AddMissingMailLogsIndexes extends AbstractMigration
{
    public function up()
    {
        $this->table('mail_logs')
            ->addIndex('email')
            ->addIndex('created_at')
            ->addIndex('delivered_at')
            ->addIndex('opened_at')
            ->addIndex('dropped_at')
            ->addIndex('spam_complained_at')
            ->addIndex('hard_bounced_at')
            ->update();
    }

    public function down()
    {
        $this->table('mail_logs')
            ->removeIndex(['email'])
            ->removeIndex(['created_at'])
            ->removeIndex(['delivered_at'])
            ->removeIndex(['opened_at'])
            ->removeIndex(['dropped_at'])
            ->removeIndex(['spam_complained_at'])
            ->removeIndex(['hard_bounced_at'])
            ->update();
    }
}
