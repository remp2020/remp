<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddExternalColumnToMailTypes extends AbstractMigration
{
    public function up(): void
    {
        $this->table('mail_types')
            ->addColumn('is_external', 'boolean', ['null' => false, 'default' => 0, 'after' => 'public_listing'])
            ->update();

        $this->table('mail_types')
            ->changeColumn('is_external', 'boolean', ['null' => false, 'after' => 'public_listing'])
            ->update();
    }

    public function down(): void
    {
        $this->table('mail_types')
            ->removeColumn('is_external')
            ->update();
    }
}
