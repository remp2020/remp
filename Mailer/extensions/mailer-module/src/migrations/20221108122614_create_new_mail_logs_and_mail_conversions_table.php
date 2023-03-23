<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateNewMailLogsAndMailConversionsTable extends AbstractMigration
{
    public function up(): void
    {
        $mailLogsRowCount = $this->query('SELECT 1 FROM mail_logs LIMIT 1;')->fetch();

        if ($mailLogsRowCount === false) {
            $this->table('mail_log_conversions')
                ->dropForeignKey('mail_log_id')
                ->save();

            $this->table('mail_log_conversions')
                ->changeColumn('mail_log_id', 'biginteger')
                ->save();

            $this->table('mail_logs')
                ->changeColumn('id', 'biginteger', ['identity' => true])
                ->addColumn('user_id', 'integer', ['null' => true, 'after' => 'email'])
                ->addIndex('user_id')
                ->save();

            $this->table('mail_log_conversions')
                ->addForeignKey('mail_log_id', 'mail_logs')
                ->save();
        } else {
            $this->query("
                CREATE TABLE mail_logs_v2 LIKE mail_logs;
                CREATE TABLE mail_log_conversions_v2 LIKE mail_log_conversions;
            ");

            $this->table('mail_log_conversions_v2')
                ->changeColumn('mail_log_id', 'biginteger')
                ->save();

            $this->table('mail_logs_v2')
                ->changeColumn('id', 'biginteger', ['identity' => true])
                ->addColumn('user_id', 'integer', ['null' => true, 'after' => 'email'])
                ->addIndex('user_id')
                ->addForeignKey('mail_template_id', 'mail_templates')
                ->addForeignKey('mail_job_id', 'mail_jobs')
                ->addForeignKey('mail_job_batch_id', 'mail_job_batch')
                ->save();

            $this->table('mail_log_conversions_v2')
                ->addForeignKey('mail_log_id', 'mail_logs_v2', 'id')
                ->save();
        }
    }

    public function down()
    {
        if ($this->hasTable('mail_log_conversions_v2')) {
            $this->table('mail_log_conversions_v2')
                ->drop()
                ->update();
        }

        if ($this->hasTable('mail_logs_v2')) {
            $this->table('mail_logs_v2')
                ->drop()
                ->update();
        }
    }
}
