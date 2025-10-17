<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class MailTypesDeletedAtIndex extends AbstractMigration
{
    public function up(): void
    {
        $this->table('mail_types')
            ->addIndex('deleted_at')
            ->update();
    }

    public function down(): void
    {
        $this->table('mail_types')
            ->removeIndex('deleted_at')
            ->update();
    }
}
