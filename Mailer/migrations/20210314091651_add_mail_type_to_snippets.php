<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddMailTypeToSnippets extends AbstractMigration
{
    public function change(): void
    {
        $this->table('mail_snippets')
            ->addColumn('mail_type_id','integer', ['default' => null, 'null' => true])
            ->addForeignKey('mail_type_id', 'mail_types')
            ->addIndex(['code', 'mail_type_id'], ['unique' => true])
            ->update();
    }
}
