<?php

use Phinx\Migration\AbstractMigration;

class MailConversionTracking extends AbstractMigration
{
    public function change()
    {
        $this->table('mail_job_batch_templates')
            ->addColumn('converted', 'integer', ['null' => false, 'default' => 0, 'after' => 'clicked'])
            ->save();

        $this->table('mail_log_conversions')
            ->addColumn('mail_log_id', 'integer', ['null' => false])
            ->addColumn('converted_at', 'datetime', ['null' => false])
            ->addForeignKey('mail_log_id', 'mail_logs')
            ->addIndex('converted_at')
            ->save();
    }
}
