<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class MailTemplatesDeletedAt extends AbstractMigration
{
    public function change(): void
    {
        $this->table('mail_templates')
            ->addIndex('deleted_at')
            ->update();
    }
}
