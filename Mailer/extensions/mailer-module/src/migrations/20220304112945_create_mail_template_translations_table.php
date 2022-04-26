<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateMailTemplateTranslationsTable extends AbstractMigration
{
    public function up(): void
    {
        $this->table('mail_template_translations')
            ->addColumn('mail_template_id', 'integer')
            ->addColumn('locale', 'string')
            ->addColumn('subject', 'string')
            ->addColumn('mail_body_text', 'text')
            ->addColumn('mail_body_html', 'text')
            ->addForeignKey('mail_template_id', 'mail_templates', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addIndex(['mail_template_id', 'locale'], ['unique' => true])
            ->create();
    }

    public function down(): void
    {
        $this->table('mail_template_translations')
            ->drop()
            ->save();
    }
}
