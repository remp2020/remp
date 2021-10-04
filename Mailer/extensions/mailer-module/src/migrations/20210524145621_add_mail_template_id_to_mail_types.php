<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddMailTemplateIdToMailTypes extends AbstractMigration
{
    public function change(): void
    {
        $this->table('mail_types')
            ->addColumn('subscribe_mail_template_id', 'integer', ['default' => null, 'null' => true, 'after' => 'mailer_alias'])
            ->addForeignKey('subscribe_mail_template_id', 'mail_templates', 'id')
            ->update();
    }
}
