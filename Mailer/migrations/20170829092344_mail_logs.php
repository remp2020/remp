<?php

use Phinx\Migration\AbstractMigration;

class MailLogs extends AbstractMigration
{
    public function change()
    {
        $this->table('mail_logs')
            ->addColumn('email', 'string')
            ->addColumn('subject', 'string', ['null' => true])
            ->addColumn('mail_template_id', 'integer', ['null' => true])
            ->addColumn('mail_job_id', 'integer', ['null' => true])
            ->addColumn('mail_sender_id', 'string', ['null' => true])
            ->addColumn('delivered_at', 'datetime', ['null' => true])
            ->addColumn('dropped_at', 'datetime', ['null' => true])
            ->addColumn('spam_complained_at', 'datetime', ['null' => true])
            ->addColumn('hard_bounced_at', 'datetime', ['null' => true])
            ->addColumn('clicked_at', 'datetime', ['null' => true])
            ->addColumn('opened_at', 'datetime', ['null' => true])
            ->addColumn('attachment_size', 'datetime', ['null' => true])
            ->addTimestamps()

            ->addForeignKey('mail_template_id', 'mail_templates', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addForeignKey('mail_job_id', 'mail_jobs', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->create();

    }
}
