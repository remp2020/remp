<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddCreatedAtIndexIntoMailTemplates extends AbstractMigration
{
    public function change(): void
    {
        $this->table('mail_templates')
            ->addIndex('created_at')
            ->update();
    }
}
