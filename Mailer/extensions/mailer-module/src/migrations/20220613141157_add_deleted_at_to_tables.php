<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddDeletedAtToTables extends AbstractMigration
{
    public function change(): void
    {
        $this->table('mail_source_template')
            ->addColumn('deleted_at', 'datetime', ['default' => null, 'null' => true, 'after' => 'updated_at'])
            ->update();

        $this->table('mail_layouts')
            ->addColumn('deleted_at', 'datetime', ['default' => null, 'null' => true, 'after' => 'updated_at'])
            ->update();

        $this->table('mail_types')
            ->addColumn('deleted_at', 'datetime', ['default' => null, 'null' => true, 'after' => 'updated_at'])
            ->update();

        $this->table('mail_templates')
            ->addColumn('deleted_at', 'datetime', ['default' => null, 'null' => true, 'after' => 'updated_at'])
            ->update();
    }
}
