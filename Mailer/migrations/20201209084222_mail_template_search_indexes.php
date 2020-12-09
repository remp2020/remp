<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class MailTemplateSearchIndexes extends AbstractMigration
{
    public function change(): void
    {
        $this->table('mail_templates')
            ->addIndex('name')
            ->addIndex('subject')
            ->addIndex('description')
            ->update();
    }
}
