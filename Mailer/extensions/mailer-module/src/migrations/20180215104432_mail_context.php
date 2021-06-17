<?php

use Phinx\Migration\AbstractMigration;

class MailContext extends AbstractMigration
{
    public function change()
    {
        $this->table('mail_logs')
            ->addColumn('context', 'string', ['after' => 'mail_sender_id', 'default' => null, 'null' => true])
            ->addIndex('context')
            ->update();

        $this->table('mail_jobs')
            ->addColumn('context', 'string', ['after' => 'status', 'null' => true, 'default' => null])
            ->update();

        $this->table('mail_job_queue')
            ->addColumn('context', 'string', ['null' => true, 'default' => null])
            ->update();
    }
}
