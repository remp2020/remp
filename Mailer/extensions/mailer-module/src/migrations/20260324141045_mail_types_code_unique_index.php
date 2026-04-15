<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class MailTypesCodeUniqueIndex extends AbstractMigration
{
    public function up(): void
    {
        if ($this->table('mail_types')->hasIndex('code')) {
            $this->table('mail_types')
                ->removeIndex(['code'])
                ->update();
        }

        $this->table('mail_types')
            ->addIndex('code', ['unique' => true])
            ->update();
    }

    public function down(): void
    {
        $this->table('mail_types')
            ->removeIndex(['code'])
            ->addIndex('code')
            ->update();
    }
}
