<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddAttachmentsEnabledToMailTemplates extends AbstractMigration
{
    public function change()
    {
        $this->table('mail_templates')
            ->addColumn('attachments_enabled', 'boolean', ['null' => false, 'default' => 1])
            ->update();

        $this->table('mail_templates')
            ->changeColumn('attachments_enabled', 'boolean', ['default' => null])
            ->update();
    }
}
