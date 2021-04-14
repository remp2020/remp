<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class MissingMailSenderIdIndex extends AbstractMigration
{
    public function change(): void
    {
        $this->table('mail_logs')
            ->addIndex('mail_sender_id')
            ->update();
    }
}
