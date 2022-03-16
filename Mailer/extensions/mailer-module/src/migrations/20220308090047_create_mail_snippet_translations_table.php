<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateMailSnippetTranslationsTable extends AbstractMigration
{
    public function up(): void
    {
        $this->table('mail_snippet_translations')
            ->addColumn('mail_snippet_id', 'integer')
            ->addColumn('locale', 'string')
            ->addColumn('text', 'text')
            ->addColumn('html', 'text')
            ->addForeignKey('mail_snippet_id', 'mail_snippets', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addIndex(['mail_snippet_id', 'locale'], ['unique' => true])
            ->create();
    }

    public function down(): void
    {
        $this->table('mail_snippet_translations')
            ->drop()
            ->save();
    }
}
