<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddExcludeFromSearchToMailTypes extends AbstractMigration
{
    public function up(): void
    {
        $this->table('mail_types')
            ->addColumn('exclude_from_search', 'boolean', ['null' => false, 'default' => 0, 'after' => 'is_external'])
            ->update();

        $this->table('mail_types')
            ->changeColumn('exclude_from_search', 'boolean', ['null' => false, 'after' => 'is_external'])
            ->update();
    }

    public function down(): void
    {
        $this->table('mail_types')
            ->removeColumn('exclude_from_search')
            ->update();
    }
}
