<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class MailJobBatchStatusIndex extends AbstractMigration
{
    public function up(): void
    {
        $this->table('mail_job_batch')
            ->addIndex('status')
            ->update();
    }

    public function down(): void
    {
        $this->table('mail_job_batch')
            ->removeIndex('status')
            ->update();
    }
}
