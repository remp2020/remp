<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddUnsubscribeMailTemplateIdToMailTypes extends AbstractMigration
{
    public function change(): void
    {
        $this->table('mail_types')
            ->addColumn('unsubscribe_mail_template_id', 'integer', ['default' => null, 'null' => true, 'after' => 'subscribe_mail_template_id'])
            ->addForeignKey('unsubscribe_mail_template_id', 'mail_templates', 'id')
            ->update();
    }
}
