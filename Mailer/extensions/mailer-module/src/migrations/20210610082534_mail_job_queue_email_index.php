<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class MailJobQueueEmailIndex extends AbstractMigration
{
    public function change(): void
    {
        $this->table('mail_job_queue')
            ->addIndex('email')
            ->update();
    }
}
