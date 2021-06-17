<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddUniqueIndexToMailTypeCodeColumn extends AbstractMigration
{
    public function change(): void
    {
        $this->table('mail_types')
            ->addIndex('code', ['unique' => true])
            ->update();
    }
}
