<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddPreheaderToMailTemplates extends AbstractMigration
{
    public function up(): void
    {
        $this->table('mail_templates')
            ->addColumn('preheader', 'string', ['null' => true, 'after' => 'subject'])
            ->update();

        $this->table('mail_template_translations')
            ->addColumn('preheader', 'string', ['null' => true, 'after' => 'subject'])
            ->update();
    }

    public function down(): void
    {
        $this->table('mail_template_translations')
            ->removeColumn('preheader')
            ->update();

        $this->table('mail_templates')
            ->removeColumn('preheader')
            ->update();
    }
}
