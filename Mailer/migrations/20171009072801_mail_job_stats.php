<?php

use Phinx\Migration\AbstractMigration;

class MailJobStats extends AbstractMigration
{
    public function change()
    {
        $this->table('mail_job_batch')
            ->addColumn('delivered', 'integer', ['null' => false, 'default' => 0])
            ->addColumn('opened', 'integer', ['null' => false, 'default' => 0])
            ->addColumn('clicked', 'integer', ['null' => false, 'default' => 0])
            ->addColumn('dropped', 'integer', ['null' => false, 'default' => 0])
            ->addColumn('spam_complained', 'integer', ['null' => false, 'default' => 0])
            ->addColumn('hard_bounced', 'integer', ['null' => false, 'default' => 0])
            ->save();

        $this->table('mail_logs')
            ->addColumn('mail_job_batch_id', 'integer', ['null' => true, 'after' => 'mail_job_id'])
            ->addForeignKey('mail_job_batch_id', 'mail_job_batch', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->save();
    }
}
