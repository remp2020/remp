<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateMailLayoutTranslationsTable extends AbstractMigration
{
    public function up(): void
    {
        $this->table('mail_layout_translations')
            ->addColumn('mail_layout_id', 'integer')
            ->addColumn('locale', 'string')
            ->addColumn('layout_text', 'text')
            ->addColumn('layout_html', 'text')
            ->addForeignKey('mail_layout_id', 'mail_layouts', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addIndex(['mail_layout_id', 'locale'], ['unique' => true])
            ->create();
    }

    public function down(): void
    {
        $this->table('mail_layout_translations')
            ->drop()
            ->save();
    }
}
