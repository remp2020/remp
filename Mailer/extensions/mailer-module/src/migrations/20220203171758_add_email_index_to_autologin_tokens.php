<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddEmailIndexToAutologinTokens extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->table('autologin_tokens')->hasIndex('email')) {
            $this->table('autologin_tokens')
                ->addIndex('email')
                ->update();
        }
    }

    public function down(): void
    {
        $this->table('autologin_tokens')
            ->removeIndex('email')
            ->update();
    }
}
