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
            ->save();

        // intentional split to two separate queries to lower the blocking time

        $this->table('mail_logs')
            ->addForeignKey('mail_job_batch_id', 'mail_job_batch', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->save();

        $sql = <<<SQL
UPDATE mail_logs
SET mail_job_batch_id = (
  SELECT id FROM mail_job_batch
  WHERE mail_job_batch.mail_job_id = mail_logs.mail_job_id
  ORDER BY mail_job_batch.id DESC LIMIT 1
)
SQL;

        $this->execute($sql);
    }
}
