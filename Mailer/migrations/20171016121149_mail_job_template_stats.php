<?php

use Phinx\Migration\AbstractMigration;

class MailJobTemplateStats extends AbstractMigration
{
    public function change()
    {
        $this->table('mail_job_batch_templates')
            ->addColumn('sent', 'integer', ['null' => false, 'default' => 0])
            ->addColumn('delivered', 'integer', ['null' => false, 'default' => 0])
            ->addColumn('opened', 'integer', ['null' => false, 'default' => 0])
            ->addColumn('clicked', 'integer', ['null' => false, 'default' => 0])
            ->addColumn('dropped', 'integer', ['null' => false, 'default' => 0])
            ->addColumn('spam_complained', 'integer', ['null' => false, 'default' => 0])
            ->addColumn('hard_bounced', 'integer', ['null' => false, 'default' => 0])
            ->save();

        $this->table('mail_job_batch')
            ->removeColumn('delivered')
            ->removeColumn('opened')
            ->removeColumn('clicked')
            ->removeColumn('dropped')
            ->removeColumn('spam_complained')
            ->removeColumn('hard_bounced')
            ->save();
    }
}
