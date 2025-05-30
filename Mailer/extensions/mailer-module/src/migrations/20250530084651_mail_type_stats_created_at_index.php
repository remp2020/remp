<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class MailTypeStatsCreatedAtIndex extends AbstractMigration
{
    public function change(): void
    {
        $this->table('mail_type_stats')
            ->addIndex('created_at')
            ->update();
    }
}
