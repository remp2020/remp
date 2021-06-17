<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class Snippets extends AbstractMigration
{
    public function change(): void
    {
        $this->table('mail_snippets')
            ->addColumn('name', 'string')
            ->addColumn('code', 'string')
            ->addColumn('text', 'text')
            ->addColumn('html', 'text')
            ->addTimestamps()
            ->create();
    }
}
